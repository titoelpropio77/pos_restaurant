<?php

namespace App\Support\Legacy;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Copia los datos de la base MySQL legacy al esquema PostgreSQL según MapaImportacion.
 *
 * Conserva los ID originales (las relaciones siguen siendo válidas) y al final ajusta las
 * secuencias de PostgreSQL. Las filas cuyo registro padre obligatorio no existe se omiten.
 */
class ImportadorLegacy
{
    private const LOTE = 500;

    /** @var array<string, array<int|string, true>> IDs importados por tabla destino */
    private array $ids = [];

    /** @var array<string, array<string, array{tipo:string, nulo:bool}>> */
    private array $esquema = [];

    private ?int $sucursalPorDefecto = null;

    private ?int $empresaId = null;

    public function __construct(
        private Connection $origen,
        private Connection $destino,
        private ?Closure $informar = null,
    ) {}

    /**
     * @param  string[]  $soloTablas  Tablas destino a importar (vacío = todas)
     * @return array<string, array{leidas:int, importadas:int, omitidas:int, avisos:array}>
     */
    public function importar(array $soloTablas = [], bool $vaciar = false): array
    {
        $mapa = MapaImportacion::tablas();
        if ($soloTablas) {
            $mapa = array_intersect_key($mapa, array_flip($soloTablas));
        }

        if ($vaciar) {
            $this->destino->statement('TRUNCATE '.implode(', ', array_keys($mapa)).' RESTART IDENTITY CASCADE');
        } else {
            foreach (array_keys($mapa) as $tabla) {
                if ($this->destino->table($tabla)->exists()) {
                    throw new RuntimeException("La tabla {$tabla} ya tiene datos. Use --vaciar para reemplazarlos.");
                }
            }
        }

        if (! isset($mapa['empresas'])) {
            $this->asegurarRestaurante();
        }

        $resumen = [];
        foreach ($mapa as $tabla => $definicion) {
            $resumen[$tabla] = $this->importarTabla($tabla, $definicion);
            $this->ajustarSecuencia($tabla);

            if ($tabla === 'empresas') {
                $this->asegurarRestaurante();
            }

            if ($tabla === 'sucursales') {
                $this->asegurarSucursal();
            }
        }

        $this->completarRelaciones();

        return $resumen;
    }

    private function importarTabla(string $tabla, array $definicion): array
    {
        $resultado = ['leidas' => 0, 'importadas' => 0, 'omitidas' => 0, 'avisos' => []];
        $origen = $definicion['origen'];

        if (! $this->origen->getSchemaBuilder()->hasTable($origen)) {
            $resultado['avisos'][] = "La tabla legacy {$origen} no existe; se omite.";
            $this->informar("{$tabla}: sin tabla de origen ({$origen})");

            return $resultado;
        }

        $columnasDestino = $this->esquema($tabla);
        $columnasOrigen = $this->origen->getSchemaBuilder()->getColumnListing($origen);
        $clave = in_array('ID', $columnasOrigen, true) ? 'ID' : (in_array('id', $columnasOrigen, true) ? 'id' : null);
        $vistos = [];
        $lote = [];

        $consulta = $this->origen->table($origen);
        $clave ? $consulta->orderBy($clave) : $consulta->orderByRaw('1');

        foreach ($consulta->lazy(self::LOTE) as $registro) {
            $resultado['leidas']++;
            $original = (array) $registro;
            $fila = $this->convertir($original, $definicion, $columnasDestino);

            $motivo = $this->validar($fila, $definicion, $vistos);
            if ($motivo !== null) {
                $resultado['omitidas']++;
                if (count($resultado['avisos']) < 20) {
                    $resultado['avisos'][] = 'Fila omitida (id '.($original[$clave ?? ''] ?? '?')."): {$motivo}";
                }

                continue;
            }

            if (isset($definicion['transformar'])) {
                $fila = array_intersect_key(($definicion['transformar'])($fila, $original), $columnasDestino);
            }

            // El sistema original era de un solo restaurante: todo queda en el restaurante destino
            if ($this->empresaId && isset($columnasDestino['empresa_id'])) {
                $fila['empresa_id'] = $this->empresaId;
            }

            $fila = $this->completarObligatorias($fila, $columnasDestino);
            $lote[] = $fila;

            if (isset($fila['id'])) {
                $this->ids[$tabla][$fila['id']] = true;
            }

            if (count($lote) >= self::LOTE) {
                $this->destino->table($tabla)->insert($lote);
                $resultado['importadas'] += count($lote);
                $lote = [];
            }
        }

        if ($lote) {
            $this->destino->table($tabla)->insert($lote);
            $resultado['importadas'] += count($lote);
        }

        $this->informar(sprintf('%s: %d importadas, %d omitidas', $tabla, $resultado['importadas'], $resultado['omitidas']));

        return $resultado;
    }

    /**
     * Renombra las columnas legacy y limpia los valores según el tipo de la columna destino.
     */
    private function convertir(array $original, array $definicion, array $columnasDestino): array
    {
        $fila = [];
        $excluir = $definicion['excluir'] ?? [];

        foreach ($original as $columna => $valor) {
            if (in_array($columna, $excluir, true)) {
                continue;
            }

            $nombre = $definicion['columnas'][$columna] ?? $this->nombreDestino($columna);
            if (! isset($columnasDestino[$nombre])) {
                continue;
            }

            // Referencias guardadas por código (p. ej. COD_GRUPO = 'PRE') se traducen al id del padre
            if ($busqueda = $definicion['buscar'][$nombre] ?? null) {
                $fila[$nombre] = $this->buscarId($busqueda[0], $busqueda[1], $valor);

                continue;
            }

            $fila[$nombre] = $this->limpiar($valor, $nombre, $columnasDestino[$nombre]['tipo']);
        }

        // Datos anteriores a la multi-sucursal (columna inexistente o NULL): sucursal principal
        if (isset($definicion['requiere']['sucursal_id']) && ($fila['sucursal_id'] ?? null) === null) {
            $fila['sucursal_id'] = $this->sucursalPorDefecto;
        }

        foreach ($definicion['fk'] ?? [] as $columna => $tablaPadre) {
            if (isset($fila[$columna]) && ! isset($this->ids[$tablaPadre][$fila[$columna]])) {
                $fila[$columna] = null;
            }
        }

        return $fila;
    }

    private function buscarId(string $tabla, string $campo, mixed $valor): ?int
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        return $this->destino->table($tabla)->where($campo, trim((string) $valor))->value('id')
            ?? (is_numeric($valor) ? (int) $valor : null);
    }

    private function validar(array $fila, array $definicion, array &$vistos): ?string
    {
        foreach ($definicion['requiere'] ?? [] as $columna => $tablaPadre) {
            if (! isset($fila[$columna]) || ! isset($this->ids[$tablaPadre][$fila[$columna]])) {
                return "{$columna}=".($fila[$columna] ?? 'NULL')." no existe en {$tablaPadre}";
            }
        }

        if ($unico = $definicion['unico'] ?? null) {
            $valor = mb_strtoupper(trim((string) ($fila[$unico] ?? '')));
            if (isset($vistos[$valor])) {
                return "{$unico} duplicado ({$valor})";
            }
            $vistos[$valor] = true;
        }

        return null;
    }

    /**
     * NOM_PROD -> nom_prod, idSucursal -> sucursal_id, precioVenta -> precio_venta.
     */
    public function nombreDestino(string $columna): string
    {
        if (preg_match('/^id([A-Z]\w*)$/', $columna, $m)) {
            return Str::snake($m[1]).'_id';
        }

        return $columna === strtoupper($columna) ? strtolower($columna) : Str::snake($columna);
    }

    private function limpiar(mixed $valor, string $columna, string $tipo): mixed
    {
        if (is_string($valor)) {
            $valor = rtrim($valor) === '' && ! str_contains($tipo, 'char') && $tipo !== 'text' ? null : $valor;
        }

        if ($valor === null) {
            return null;
        }

        if (str_ends_with($columna, '_id') && ($valor === 0 || $valor === '0')) {
            return null;
        }

        return match (true) {
            str_starts_with($tipo, 'date'), str_starts_with($tipo, 'timestamp') => str_starts_with((string) $valor, '0000-00-00') ? null : $valor,
            $tipo === 'boolean' => in_array(strtoupper((string) $valor), ['1', 'X', 'S', 'SI', 'TRUE'], true),
            in_array($tipo, ['integer', 'bigint', 'smallint'], true) => is_numeric($valor) ? (int) $valor : null,
            $tipo === 'numeric' => is_numeric($valor) ? $valor : null,
            str_contains($tipo, 'char'), $tipo === 'text' => (string) $valor,
            default => $valor,
        };
    }

    /**
     * Las columnas NOT NULL sin valor reciben su valor por defecto o uno neutro según su tipo.
     */
    private function completarObligatorias(array $fila, array $columnasDestino): array
    {
        foreach ($columnasDestino as $columna => $info) {
            if ($info['nulo'] || ($fila[$columna] ?? null) !== null || in_array($columna, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            $fila[$columna] = $info['default'] ?? match (true) {
                $info['tipo'] === 'boolean' => false,
                in_array($info['tipo'], ['integer', 'bigint', 'smallint', 'numeric'], true) => 0,
                str_starts_with($info['tipo'], 'date') => '1900-01-01',
                str_starts_with($info['tipo'], 'time') => '00:00',
                default => '',
            };
        }

        foreach (['created_at', 'updated_at'] as $marca) {
            if (isset($columnasDestino[$marca]) && ! isset($fila[$marca])) {
                $fila[$marca] = now();
            }
        }

        return $fila;
    }

    /**
     * Valor literal de un DEFAULT de PostgreSQL ('ACTIVO'::character varying -> ACTIVO).
     * Las expresiones (nextval, now()) devuelven null.
     */
    private function valorPorDefecto(?string $default): mixed
    {
        return match (true) {
            $default === null => null,
            (bool) preg_match("/^'(.*)'::[\\w\\s]+$/s", $default, $m) => $m[1],
            is_numeric($default) => $default + 0,
            in_array($default, ['true', 'false'], true) => $default === 'true',
            default => null,
        };
    }

    private function esquema(string $tabla): array
    {
        return $this->esquema[$tabla] ??= collect($this->destino->select(
            'select column_name, data_type, is_nullable, column_default from information_schema.columns where table_schema = current_schema() and table_name = ?',
            [$tabla],
        ))->mapWithKeys(fn ($c) => [$c->column_name => [
            'tipo' => $c->data_type,
            'nulo' => $c->is_nullable === 'YES',
            'default' => $this->valorPorDefecto($c->column_default),
        ]])->all();
    }

    private function asegurarRestaurante(): void
    {
        $this->empresaId = $this->destino->table('empresas')->orderBy('id')->value('id')
            ?? $this->destino->table('empresas')->insertGetId([
                'nombre' => 'MI RESTAURANTE', 'estado' => 'ACTIVO', 'created_at' => now(), 'updated_at' => now(),
            ]);
    }

    private function asegurarSucursal(): void
    {
        $this->sucursalPorDefecto = $this->destino->table('sucursales')->orderBy('id')->value('id');

        if (! $this->sucursalPorDefecto) {
            $this->sucursalPorDefecto = $this->destino->table('sucursales')->insertGetId([
                'nombre' => 'CASA MATRIZ', 'estado' => 'ACTIVO', 'empresa_id' => $this->empresaId, 'es_matriz' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->ids['sucursales'][$this->sucursalPorDefecto] = true;
            $this->informar('El sistema legacy no tenía sucursales: se creó CASA MATRIZ');
        }

        $this->destino->table('sucursales')->where('id', $this->sucursalPorDefecto)->update(['es_matriz' => true]);
    }

    private function ajustarSecuencia(string $tabla): void
    {
        if (isset($this->esquema($tabla)['id'])) {
            $this->destino->statement(
                "select setval(pg_get_serial_sequence(?, 'id'), coalesce((select max(id) from {$tabla}), 0) + 1, false)",
                [$tabla],
            );
        }
    }

    /**
     * Relaciones que en el sistema legacy se guardaban por nombre y no por id.
     */
    private function completarRelaciones(): void
    {
        $this->destino->statement(
            'update ingresos_usuario i set usuario_id = u.id from usuarios u where i.usuario_id is null and u.usuario = upper(i.usuario)'
        );
    }

    private function informar(string $mensaje): void
    {
        if ($this->informar) {
            ($this->informar)($mensaje);
        }
    }
}
