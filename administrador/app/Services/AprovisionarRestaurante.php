<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Empresa;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Deja un restaurante nuevo listo para ingresar: casa matriz, almacén, numeración,
 * catálogos base y usuario dueño.
 *
 * La dosificación nace vencida a propósito: el POS no permite facturar con ella hasta que el
 * dueño cargue en Facturación SIN la autorización real de Impuestos Nacionales.
 */
class AprovisionarRestaurante
{
    private const CORRELATIVO_VENTAS = 'ventas';

    private const MOTIVOS = ['VENTA', 'COMPRA', 'TRASPASO', 'AJUSTE', 'MERMA'];

    private const UNIDADES = [['UNIDAD', 'UND'], ['KILOGRAMO', 'KG'], ['GRAMO', 'GR'], ['LITRO', 'LT'], ['MILILITRO', 'ML']];

    private const IMPUESTOS = [['IVA DEBITO FISCAL', 13], ['IVA CREDITO FISCAL', 13], ['IT', 3], ['IT POR PAGAR', 3]];

    /**
     * @param  array  $restaurante  datos de empresas
     * @param  array  $matriz  datos de sucursales de la casa matriz
     * @param  array  $dueno  nombres, usuario, password
     */
    public function crear(array $restaurante, array $matriz, array $dueno): Empresa
    {
        return DB::transaction(function () use ($restaurante, $matriz, $dueno) {
            $empresa = Empresa::create($restaurante + ['opciones' => Empresa::opcionesIniciales()]);

            $this->catalogosBase($empresa);
            $sucursal = $this->agregarSucursal($empresa, $matriz, true);

            $rol = Rol::delAmbito(Rol::RESTAURANTE)->where('nombre', 'Dueño')->with('permisos')->firstOrFail();

            $usuario = new Usuario([
                'nombres' => mb_strtoupper($dueno['nombres']),
                'usuario' => $dueno['usuario'],
                'password' => $dueno['password'],
                'cargo' => 'DUEÑO',
                'estado' => 'ACTIVO',
                'rol_id' => $rol->id,
                'nivel' => Usuario::nivelDe($rol),
            ]);
            $usuario->empresa_id = $empresa->id;
            $usuario->save();

            $this->asignarPuntoVenta($usuario, [
                'sucursal_id' => $sucursal->id,
                'almacen_por_defecto_id' => $sucursal->almacenes()->value('id'),
                'venta_multialmacen' => true,
                'puede_vender' => true,
            ]);

            return $empresa;
        });
    }

    public function agregarSucursal(Empresa $empresa, array $datos, bool $matriz = false): Sucursal
    {
        return DB::transaction(function () use ($empresa, $datos, $matriz) {
            $sucursal = Sucursal::create($datos + [
                'empresa_id' => $empresa->id,
                'es_matriz' => $matriz,
                'estado' => 'ACTIVO',
                'ip_impresion' => 'localhost',
            ]);

            Almacen::create(['nombre' => 'ALMACÉN PRINCIPAL', 'sucursal_id' => $sucursal->id, 'empresa_id' => $empresa->id]);

            Correlativo::create([
                'tabla' => self::CORRELATIVO_VENTAS, 'sucursal_id' => $sucursal->id, 'empresa_id' => $empresa->id,
                'folio' => 0, 'folior' => 0, 'ordendia' => 0,
            ]);

            Dosificacion::create([
                'sucursal_id' => $sucursal->id,
                'empresa_id' => $empresa->id,
                'nomemp' => $empresa->nombre,
                'nompro' => $empresa->propietario,
                'nitruc' => $empresa->nit,
                'sucursal' => $sucursal->nombre,
                'diremp' => $sucursal->direccion ?: 'S/D',
                'telemp' => $sucursal->telefono ?: '0',
                'inifac' => 1,
                'facact' => 0,
                'facactr' => 0,
                'hinitm' => '06:00',
                'hfintm' => '16:00',
                'fecha_lim' => today()->subDay()->toDateString(),
            ]);

            return $sucursal;
        });
    }

    public function asignarPuntoVenta(Usuario $usuario, array $datos): void
    {
        PuntoVenta::updateOrCreate(['usuario_id' => $usuario->id], $datos + [
            'empresa_id' => $usuario->empresa_id,
            'fecha_inicio' => today(),
        ]);
    }

    private function catalogosBase(Empresa $empresa): void
    {
        $base = ['empresa_id' => $empresa->id, 'created_at' => now(), 'updated_at' => now()];

        DB::table('motivos_movimiento')->insert(array_map(fn ($n) => $base + ['nombre' => $n], self::MOTIVOS));
        DB::table('unidades_medida')->insert(array_map(fn ($u) => $base + ['nombre' => $u[0], 'abreviatura' => $u[1]], self::UNIDADES));
        DB::table('impuestos')->insert(array_map(fn ($i) => $base + ['nombre' => $i[0], 'valor' => $i[1]], self::IMPUESTOS));
        DB::table('cajas')->insert($base + ['nombre' => 'CAJA DE VENTA', 'moneda' => 'Bs']);
        DB::table('gestiones')->insert($base + [
            'nombre_gestion' => (string) now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'estado' => 1,
        ]);
    }
}
