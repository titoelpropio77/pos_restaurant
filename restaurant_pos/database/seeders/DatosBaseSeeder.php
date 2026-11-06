<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Ciudad;
use App\Models\Correlativo;
use App\Models\Dosificacion;
use App\Models\Empresa;
use App\Models\Gestion;
use App\Models\Impuesto;
use App\Models\IngresoUsuario;
use App\Models\Moneda;
use App\Models\MotivoMovimiento;
use App\Models\Pais;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\UnidadMedida;
use App\Models\Usuario;
use App\Support\Restaurante;
use Illuminate\Database\Seeder;

/**
 * Datos mínimos para operar el POS: restaurante, casa matriz con dosificación y correlativo,
 * almacén, catálogos básicos y un usuario dueño. Los demás restaurantes se crean desde la
 * app administrador.
 */
class DatosBaseSeeder extends Seeder
{
    public function run(): void
    {
        $bolivia = Pais::firstOrCreate(['nombre' => 'BOLIVIA']);
        $ciudad = Ciudad::firstOrCreate(['nombre' => 'LA PAZ', 'pais_id' => $bolivia->id]);

        Moneda::firstOrCreate(['abreviatura' => 'Bs'], ['nombre' => 'BOLIVIANOS']);
        Moneda::firstOrCreate(['abreviatura' => 'USD'], ['nombre' => 'DÓLARES']);

        $empresa = Empresa::orderBy('id')->first()
            ?? Empresa::create(['nombre' => env('POS_EMPRESA', 'MESATEK')]);

        Restaurante::usar($empresa->id);

        $sucursal = Sucursal::firstOrCreate(
            ['nombre' => 'CASA MATRIZ'],
            ['ciudad_id' => $ciudad->id, 'direccion' => 'S/D', 'ip_impresion' => 'localhost'],
        );
        $sucursal->forceFill(['es_matriz' => true])->save();

        Dosificacion::firstOrCreate(['sucursal_id' => $sucursal->id], [
            'nomemp' => $empresa->nombre,
            'nompro' => 'PROPIETARIO',
            'ciupai' => 'LA PAZ - BOLIVIA',
            'nitruc' => '1234567',
            'sucursal' => 'CASA MATRIZ',
            'diremp' => 'S/D',
            'telemp' => '0',
            'inifac' => 1,
            'facact' => 0,
            'facactr' => 0,
            'hinitm' => '06:00',
            'hfintm' => '16:00',
            // Dosificación de prueba: reemplazar por la otorgada por Impuestos Nacionales
            'nroautori' => '29040011007',
            'llave' => '9rCB7Sv4X29d)5k7N%3ab89p-3(5[A',
            'fecha_lim' => now()->addYear()->toDateString(),
            'leyenda1' => 'ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS. EL USO ILÍCITO DE ÉSTA SERÁ SANCIONADO DE ACUERDO A LEY',
        ]);

        Correlativo::firstOrCreate(
            ['tabla' => config('posgourmet.correlativo_ventas'), 'sucursal_id' => $sucursal->id],
            ['folio' => 0, 'folior' => 0, 'ordendia' => 0],
        );

        $almacen = Almacen::firstOrCreate(['nombre' => 'ALMACÉN PRINCIPAL', 'sucursal_id' => $sucursal->id]);

        foreach (['VENTA', 'COMPRA', 'TRASPASO', 'AJUSTE', 'MERMA'] as $motivo) {
            MotivoMovimiento::firstOrCreate(['nombre' => $motivo]);
        }

        foreach ([['UNIDAD', 'UND'], ['KILOGRAMO', 'KG'], ['GRAMO', 'GR'], ['LITRO', 'LT'], ['MILILITRO', 'ML']] as [$nombre, $abreviatura]) {
            UnidadMedida::firstOrCreate(['nombre' => $nombre], ['abreviatura' => $abreviatura]);
        }

        foreach ([['IVA DEBITO FISCAL', 13], ['IVA CREDITO FISCAL', 13], ['IT', 3], ['IT POR PAGAR', 3]] as [$nombre, $valor]) {
            Impuesto::firstOrCreate(['nombre' => $nombre], ['valor' => $valor]);
        }

        Caja::firstOrCreate(['nombre' => 'CAJA DE VENTA'], ['moneda' => 'Bs']);

        Gestion::firstOrCreate(['nombre_gestion' => (string) now()->year], [
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'estado' => 1,
        ]);

        $admin = Usuario::firstOrCreate(['usuario' => 'ADMIN'], [
            'nombres' => 'ADMINISTRADOR',
            'cargo' => 'ADMINISTRADOR',
            'password' => mb_strtoupper(env('POS_ADMIN_PASSWORD', 'admin')),
            'nivel' => Usuario::NIVEL_ADMIN,
            'rol_id' => Rol::delRestaurante()->where('nombre', 'Dueño')->value('id'),
        ]);

        PuntoVenta::firstOrCreate(['usuario_id' => $admin->id], [
            'sucursal_id' => $sucursal->id,
            'almacen_por_defecto_id' => $almacen->id,
            'venta_multialmacen' => true,
            'fecha_inicio' => today(),
        ]);

        if (! IngresoUsuario::exists()) {
            IngresoUsuario::create([
                'ingresos' => 0,
                'usuario_id' => $admin->id,
                'usuario' => $admin->usuario,
                'vfecha' => today(),
                'hora' => now()->format('G:i'),
                'cam_of' => 6.96,
            ]);
        }
    }
}
