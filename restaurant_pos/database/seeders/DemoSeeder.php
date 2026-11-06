<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Insumo;
use App\Models\InsumoAlmacen;
use App\Models\InsumoProducto;
use App\Models\Mesa;
use App\Models\Personal;
use App\Models\Producto;
use App\Models\ProductoOpcion;
use App\Models\ProductoSucursal;
use App\Models\PuntoVenta;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

/**
 * Menú, mesas y personal de ejemplo para probar el POS en desarrollo.
 */
class DemoSeeder extends Seeder
{
    private const MENU = [
        'PLATOS' => ['#d9534f', [['PIQUE MACHO', 65], ['SILPANCHO', 35], ['MILANESA DE POLLO', 38], ['FRICASÉ', 40], ['SAJTA DE POLLO', 36]]],
        'BEBIDAS' => ['#337ab7', [['COCA COLA 2L', 15], ['JUGO DE PIÑA', 10], ['LIMONADA', 8], ['CERVEZA PACEÑA', 20]]],
        'POSTRES' => ['#5cb85c', [['HELADO', 12], ['FLAN', 10], ['TORTA DE CHOCOLATE', 14]]],
    ];

    /** Fotos de ejemplo (public/abms1/imagenes/imagenboton); el POS muestra las rutas que empiezan con "abms1/". */
    private const FOTOS = [
        'PIQUE MACHO' => 'pique-macho.jpg',
        'SILPANCHO' => 'silpancho.jpg',
        'MILANESA DE POLLO' => 'milanesa-de-pollo.jpg',
        'FRICASÉ' => 'fricase.jpg',
        'SAJTA DE POLLO' => 'sajta-de-pollo.jpg',
    ];

    public function run(): void
    {
        $sucursal = Sucursal::where('nombre', 'CASA MATRIZ')->firstOrFail();
        $almacen = Almacen::where('sucursal_id', $sucursal->id)->firstOrFail();

        $orden = 0;
        foreach (self::MENU as $nombreCategoria => [$color, $productos]) {
            $categoria = Categoria::firstOrCreate(['nom_grupo' => $nombreCategoria], ['colores' => $color, 'orden' => ++$orden]);
            $categoria->update(['cod_grupo' => (string) $categoria->id, 'grupo' => $categoria->id]);

            foreach ($productos as [$nombre, $precio]) {
                $producto = Producto::firstOrCreate(['nom_prod' => $nombre], [
                    'pre_venta' => $precio,
                    'cantidad' => 1,
                    'unid' => 1,
                    'categoria_id' => $categoria->id,
                    'colores' => $color,
                    'presa' => '',
                    'imgnormal' => isset(self::FOTOS[$nombre]) ? 'abms1/imagenes/imagenboton/'.self::FOTOS[$nombre] : '',
                    'tiempo' => '',
                ]);
                $producto->update(['cod_prod' => $producto->id]);

                ProductoSucursal::firstOrCreate(
                    ['producto_id' => $producto->id, 'sucursal_id' => $sucursal->id],
                    array_merge(
                        array_fill_keys(['lun', 'mar', 'mier', 'jue', 'vie', 'sab', 'dom'], 'x'),
                        [
                            'categoria_id' => $categoria->id,
                            'precio_venta' => $precio,
                            'precio_venta_normal' => $precio,
                            'hora_inicio' => '00:00',
                            'hora_fin' => '00:00',
                            'impcoc' => $nombreCategoria === 'PLATOS' ? 'x' : '',
                        ],
                    ),
                );
            }
        }

        // Opciones del pique: al venderlo el POS ofrece estas sugerencias (campo "presa" con valor)
        $pique = Producto::where('nom_prod', 'PIQUE MACHO')->first();
        $pique->update(['presa' => 'OPCIONES']);
        foreach (['SIN PICANTE', 'EXTRA PAPAS', 'TÉRMINO MEDIO'] as $opcion) {
            ProductoOpcion::firstOrCreate(['producto_id' => $pique->id, 'opcion' => $opcion], ['nom_prod' => $pique->nom_prod]);
        }

        // Receta: la limonada consume limón y azúcar del almacén
        $limon = Insumo::firstOrCreate(['nom_insumo' => 'LIMÓN'], ['medida' => 'UND', 'stock_min' => 20]);
        $azucar = Insumo::firstOrCreate(['nom_insumo' => 'AZÚCAR'], ['medida' => 'GR', 'stock_min' => 1000]);
        foreach ([[$limon, 100], [$azucar, 5000]] as [$insumo, $stock]) {
            $insumo->update(['cod_ins' => (string) $insumo->id]);
            InsumoAlmacen::firstOrCreate(['insumo_id' => $insumo->id, 'almacen_id' => $almacen->id], ['stock_actual' => $stock]);
        }
        $limonada = Producto::where('nom_prod', 'LIMONADA')->first();
        InsumoProducto::firstOrCreate(['producto_id' => $limonada->id, 'insumo_id' => $limon->id], ['cantidad' => 2]);
        InsumoProducto::firstOrCreate(['producto_id' => $limonada->id, 'insumo_id' => $azucar->id], ['cantidad' => 30]);

        for ($i = 1; $i <= 12; $i++) {
            Mesa::firstOrCreate(['sucursal_id' => $sucursal->id, 'nro_mesa' => (string) $i], ['orden' => $i]);
        }

        foreach ([['JUAN PÉREZ', '#f0ad4e', '1111'], ['MARÍA QUISPE', '#5bc0de', '2222']] as [$nombre, $color, $codigo]) {
            Personal::firstOrCreate(['nom_empl' => $nombre], ['cargo' => 'MESERO', 'coloremp' => $color, 'codigo' => $codigo, 'sucursal_id' => $sucursal->id]);
        }

        $cajero = Usuario::firstOrCreate(['usuario' => 'CAJERO'], [
            'nombres' => 'CAJERO DE PRUEBA',
            'cargo' => 'CAJERO',
            'password' => 'CAJERO',
            'nivel' => Usuario::NIVEL_CAJERO,
            'rol_id' => Rol::delRestaurante()->where('nombre', 'Cajero')->value('id'),
        ]);
        PuntoVenta::firstOrCreate(['usuario_id' => $cajero->id], [
            'sucursal_id' => $sucursal->id,
            'almacen_por_defecto_id' => $almacen->id,
        ]);
    }
}
