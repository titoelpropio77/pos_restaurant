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
class LegacyImporter
{
    private const BATCH = 500;

    /** @var array<string, array<int|string, true>> IDs importados por tabla destino */
    private array $ids = [];

    /** @var array<string, array<string, array{tipo:string, nulo:bool}>> */
    private array $schema = [];

    private ?int $branchDefault = null;

    private ?int $restaurantId = null;

    public function __construct(
        private Connection $source,
        private Connection $destination,
        private ?Closure $report = null,
    ) {}

    /**
     * @param  string[]  $soloTablas  Tablas destino a importar (vacío = todas)
     * @return array<string, array{leidas:int, importadas:int, omitidas:int, avisos:array}>
     */
    public function import(array $onlyTables = [], bool $truncate = false): array
    {
        $map = LegacyImportMap::tables();
        if ($onlyTables) {
            $map = array_intersect_key($map, array_flip($onlyTables));
        }

        if ($truncate) {
            $this->destination->statement('TRUNCATE '.implode(', ', array_keys($map)).' RESTART IDENTITY CASCADE');
        } else {
            foreach (array_keys($map) as $table_name) {
                if ($this->destination->table($table_name)->exists()) {
                    throw new RuntimeException("La tabla {$table_name} ya tiene datos. Use --vaciar para reemplazarlos.");
                }
            }
        }

        if (! isset($map['restaurants'])) {
            $this->ensureRestaurant();
        }

        $summary = [];
        foreach ($map as $table_name => $definition) {
            $summary[$table_name] = $this->importTable($table_name, $definition);
            $this->adjustSequence($table_name);

            if ($table_name === 'restaurants') {
                $this->ensureRestaurant();
            }

            if ($table_name === 'branches') {
                $this->ensureBranch();
            }
        }

        $this->completeRelations();

        return $summary;
    }

    private function importTable(string $table_name, array $definition): array
    {
        $result = ['read' => 0, 'imported' => 0, 'skipped' => 0, 'warnings' => []];
        $source = $definition['source'];

        if (! $this->source->getSchemaBuilder()->hasTable($source)) {
            $result['warnings'][] = "La tabla legacy {$source} no existe; se omite.";
            $this->report("{$table_name}: sin tabla de origen ({$source})");

            return $result;
        }

        $columnsDestination = $this->schema($table_name);
        $columnsSource = $this->source->getSchemaBuilder()->getColumnListing($source);
        $key = in_array('ID', $columnsSource, true) ? 'ID' : (in_array('id', $columnsSource, true) ? 'id' : null);
        $seen = [];
        $batch = [];

        $query = $this->source->table($source);
        $key ? $query->orderBy($key) : $query->orderByRaw('1');

        foreach ($query->lazy(self::BATCH) as $record) {
            $result['read']++;
            $original = (array) $record;
            $row = $this->convert($original, $definition, $columnsDestination);

            $reason = $this->validate($row, $definition, $seen);
            if ($reason !== null) {
                $result['skipped']++;
                if (count($result['warnings']) < 20) {
                    $result['warnings'][] = 'Fila omitida (id '.($original[$key ?? ''] ?? '?')."): {$reason}";
                }

                continue;
            }

            if (isset($definition['transform'])) {
                $row = array_intersect_key(($definition['transform'])($row, $original), $columnsDestination);
            }

            // El sistema original era de un solo restaurante: todo queda en el restaurante destino
            if ($this->restaurantId && isset($columnsDestination['restaurant_id'])) {
                $row['restaurant_id'] = $this->restaurantId;
            }

            $row = $this->fillRequired($row, $columnsDestination);
            $batch[] = $row;

            if (isset($row['id'])) {
                $this->ids[$table_name][$row['id']] = true;
            }

            if (count($batch) >= self::BATCH) {
                $this->destination->table($table_name)->insert($batch);
                $result['imported'] += count($batch);
                $batch = [];
            }
        }

        if ($batch) {
            $this->destination->table($table_name)->insert($batch);
            $result['imported'] += count($batch);
        }

        $this->report(sprintf('%s: %d importadas, %d omitidas', $table_name, $result['imported'], $result['skipped']));

        return $result;
    }

    /**
     * Renombra las columnas legacy y limpia los valores según el tipo de la columna destino.
     */
    private function convert(array $original, array $definition, array $columnsDestination): array
    {
        $row = [];
        $exclude = $definition['exclude'] ?? [];

        foreach ($original as $column => $value) {
            if (in_array($column, $exclude, true)) {
                continue;
            }

            $name = $definition['columns'][$column] ?? $this->nameDestination($column);
            if (! isset($columnsDestination[$name])) {
                continue;
            }

            // Referencias guardadas por código (p. ej. COD_GRUPO = 'PRE') se traducen al id del padre
            if ($search = $definition['search'][$name] ?? null) {
                $row[$name] = $this->searchId($search[0], $search[1], $value);

                continue;
            }

            $row[$name] = $this->clear($value, $name, $columnsDestination[$name]['type']);
        }

        // Datos anteriores a la multi-sucursal (columna inexistente o NULL): sucursal principal
        if (isset($definition['requires']['branch_id']) && ($row['branch_id'] ?? null) === null) {
            $row['branch_id'] = $this->branchDefault;
        }

        foreach ($definition['fk'] ?? [] as $column => $tableParent) {
            if (isset($row[$column]) && ! isset($this->ids[$tableParent][$row[$column]])) {
                $row[$column] = null;
            }
        }

        return $row;
    }

    private function searchId(string $table_name, string $field, mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->destination->table($table_name)->where($field, trim((string) $value))->value('id')
            ?? (is_numeric($value) ? (int) $value : null);
    }

    private function validate(array $row, array $definition, array &$seen): ?string
    {
        foreach ($definition['requires'] ?? [] as $column => $tableParent) {
            if (! isset($row[$column]) || ! isset($this->ids[$tableParent][$row[$column]])) {
                return "{$column}=".($row[$column] ?? 'NULL')." no existe en {$tableParent}";
            }
        }

        if ($unique = $definition['unique'] ?? null) {
            $value = mb_strtoupper(trim((string) ($row[$unique] ?? '')));
            if (isset($seen[$value])) {
                return "{$unique} duplicado ({$value})";
            }
            $seen[$value] = true;
        }

        return null;
    }

    /**
     * NOM_PROD -> nom_prod, idSucursal -> sucursal_id, precioVenta -> precio_venta.
     */
    public function nameDestination(string $column): string
    {
        $legacy = match (true) {
            (bool) preg_match('/^id([A-Z]\w*)$/', $column, $m) => Str::snake($m[1]).'_id',
            $column === strtoupper($column) => strtolower($column),
            default => Str::snake($column),
        };

        return LegacyImportMap::COLUMNS[$legacy] ?? $legacy;
    }

    private function clear(mixed $value, string $column, string $type): mixed
    {
        if (is_string($value)) {
            $value = rtrim($value) === '' && ! str_contains($type, 'char') && $type !== 'text' ? null : $value;
        }

        if ($value === null) {
            return null;
        }

        if (str_ends_with($column, '_id') && ($value === 0 || $value === '0')) {
            return null;
        }

        return match (true) {
            str_starts_with($type, 'date'), str_starts_with($type, 'timestamp') => str_starts_with((string) $value, '0000-00-00') ? null : $value,
            $type === 'boolean' => in_array(strtoupper((string) $value), ['1', 'X', 'S', 'SI', 'TRUE'], true),
            in_array($type, ['integer', 'bigint', 'smallint'], true) => is_numeric($value) ? (int) $value : null,
            $type === 'numeric' => is_numeric($value) ? $value : null,
            str_contains($type, 'char'), $type === 'text' => (string) $value,
            default => $value,
        };
    }

    /**
     * Las columnas NOT NULL sin valor reciben su valor por defecto o uno neutro según su tipo.
     */
    private function fillRequired(array $row, array $columnsDestination): array
    {
        foreach ($columnsDestination as $column => $info) {
            if ($info['nulo'] || ($row[$column] ?? null) !== null || in_array($column, ['id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            $row[$column] = $info['default'] ?? match (true) {
                $info['type'] === 'boolean' => false,
                in_array($info['type'], ['integer', 'bigint', 'smallint', 'numeric'], true) => 0,
                str_starts_with($info['type'], 'date') => '1900-01-01',
                str_starts_with($info['type'], 'time') => '00:00',
                default => '',
            };
        }

        foreach (['created_at', 'updated_at'] as $brand) {
            if (isset($columnsDestination[$brand]) && ! isset($row[$brand])) {
                $row[$brand] = now();
            }
        }

        return $row;
    }

    /**
     * Valor literal de un DEFAULT de PostgreSQL ('ACTIVE'::character varying -> ACTIVE).
     * Las expresiones (nextval, now()) devuelven null.
     */
    private function valueDefault(?string $default): mixed
    {
        return match (true) {
            $default === null => null,
            (bool) preg_match("/^'(.*)'::[\\w\\s]+$/s", $default, $m) => $m[1],
            is_numeric($default) => $default + 0,
            in_array($default, ['true', 'false'], true) => $default === 'true',
            default => null,
        };
    }

    private function schema(string $table_name): array
    {
        return $this->schema[$table_name] ??= collect($this->destination->select(
            'select column_name, data_type, is_nullable, column_default from information_schema.columns where table_schema = current_schema() and table_name = ?',
            [$table_name],
        ))->mapWithKeys(fn ($c) => [$c->column_name => [
            'type' => $c->data_type,
            'nulo' => $c->is_nullable === 'YES',
            'default' => $this->valueDefault($c->column_default),
        ]])->all();
    }

    private function ensureRestaurant(): void
    {
        $this->restaurantId = $this->destination->table('restaurants')->orderBy('id')->value('id')
            ?? $this->destination->table('restaurants')->insertGetId([
                'name' => 'MI RESTAURANTE', 'status' => 'ACTIVE', 'created_at' => now(), 'updated_at' => now(),
            ]);
    }

    private function ensureBranch(): void
    {
        $this->branchDefault = $this->destination->table('branches')->orderBy('id')->value('id');

        if (! $this->branchDefault) {
            $this->branchDefault = $this->destination->table('branches')->insertGetId([
                'name' => 'CASA MATRIZ', 'status' => 'ACTIVE', 'restaurant_id' => $this->restaurantId, 'is_main' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->ids['branches'][$this->branchDefault] = true;
            $this->report('El sistema legacy no tenía sucursales: se creó CASA MATRIZ');
        }

        $this->destination->table('branches')->where('id', $this->branchDefault)->update(['is_main' => true]);
    }

    private function adjustSequence(string $table_name): void
    {
        if (isset($this->schema($table_name)['id'])) {
            $this->destination->statement(
                "select setval(pg_get_serial_sequence(?, 'id'), coalesce((select max(id) from {$table_name}), 0) + 1, false)",
                [$table_name],
            );
        }
    }

    /**
     * Relaciones que en el sistema legacy se guardaban por nombre y no por id.
     */
    private function completeRelations(): void
    {
        $this->destination->statement(
            'insert into restaurant_user (restaurant_id, user_id) select ?, id from users on conflict do nothing',
            [$this->restaurantId],
        );

        $this->destination->statement(
            'update ingresos_usuario i set usuario_id = u.id from usuarios u where i.usuario_id is null and u.usuario = upper(i.usuario)'
        );
    }

    private function report(string $message): void
    {
        if ($this->report) {
            ($this->report)($message);
        }
    }
}
