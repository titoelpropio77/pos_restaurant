<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\RecipeItem;
use App\Models\Staff;
use App\Models\Supply;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
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

    /** Fotos de ejemplo (public/legacy/images/buttons); el POS muestra las rutas que empiezan con "legacy/". */
    private const PHOTOS = [
        'PIQUE MACHO' => 'pique-macho.jpg',
        'SILPANCHO' => 'silpancho.jpg',
        'MILANESA DE POLLO' => 'milanesa-de-pollo.jpg',
        'FRICASÉ' => 'fricase.jpg',
        'SAJTA DE POLLO' => 'sajta-de-pollo.jpg',
    ];

    public function run(): void
    {
        $branch = Branch::where('name', 'CASA MATRIZ')->firstOrFail();
        $warehouse = Warehouse::where('branch_id', $branch->id)->firstOrFail();

        $order_number = 0;
        foreach (self::MENU as $nameCategory => [$color, $products]) {
            $category = Category::firstOrCreate(['group_name' => $nameCategory], ['color' => $color, 'order_number' => ++$order_number]);
            $category->update(['group_code' => (string) $category->id, 'category_group' => $category->id]);

            foreach ($products as [$name, $price]) {
                $product = Product::firstOrCreate(['product_name' => $name], [
                    'sale_price' => $price,
                    'quantity' => 1,
                    'unit_factor' => 1,
                    'category_id' => $category->id,
                    'color' => $color,
                    'options_mode' => '',
                    'image_path' => isset(self::PHOTOS[$name]) ? 'legacy/images/buttons/'.self::PHOTOS[$name] : '',
                    'timed' => '',
                ]);
                $product->update(['product_code' => $product->id]);

                BranchProduct::firstOrCreate(
                    ['product_id' => $product->id, 'branch_id' => $branch->id],
                    array_merge(
                        array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], 'x'),
                        [
                            'category_id' => $category->id,
                            'sale_price' => $price,
                            'regular_price' => $price,
                            'start_time' => '00:00',
                            'end_time' => '00:00',
                            'print_kitchen' => $nameCategory === 'PLATOS' ? 'x' : '',
                        ],
                    ),
                );
            }
        }

        // Opciones del pique: al venderlo el POS ofrece estas sugerencias (campo "presa" con valor)
        $pique = Product::where('product_name', 'PIQUE MACHO')->first();
        $pique->update(['options_mode' => 'OPCIONES']);
        foreach (['SIN PICANTE', 'EXTRA PAPAS', 'TÉRMINO MEDIO'] as $option_name) {
            ProductOption::firstOrCreate(['product_id' => $pique->id, 'option_name' => $option_name], ['product_name' => $pique->product_name]);
        }

        // Receta: la limonada consume limón y azúcar del almacén
        $limon = Supply::firstOrCreate(['supply_name' => 'LIMÓN'], ['measure' => 'UND', 'min_stock' => 20]);
        $azucar = Supply::firstOrCreate(['supply_name' => 'AZÚCAR'], ['measure' => 'GR', 'min_stock' => 1000]);
        foreach ([[$limon, 100], [$azucar, 5000]] as [$supply, $stock]) {
            $supply->update(['supply_code' => (string) $supply->id]);
            WarehouseStock::firstOrCreate(['supply_id' => $supply->id, 'warehouse_id' => $warehouse->id], ['current_stock' => $stock]);
        }
        $limonada = Product::where('product_name', 'LIMONADA')->first();
        RecipeItem::firstOrCreate(['product_id' => $limonada->id, 'supply_id' => $limon->id], ['quantity' => 2]);
        RecipeItem::firstOrCreate(['product_id' => $limonada->id, 'supply_id' => $azucar->id], ['quantity' => 30]);

        for ($i = 1; $i <= 12; $i++) {
            DiningTable::firstOrCreate(['branch_id' => $branch->id, 'table_number' => (string) $i], ['order_number' => $i]);
        }

        foreach ([['JUAN PÉREZ', '#f0ad4e', '1111'], ['MARÍA QUISPE', '#5bc0de', '2222']] as [$name, $color, $code]) {
            Staff::firstOrCreate(['staff_name' => $name], ['position' => 'MESERO', 'staff_color' => $color, 'code' => $code, 'branch_id' => $branch->id]);
        }

        $cashier = User::firstOrCreate(['email' => 'cajero@mesatek.local'], [
            'username' => 'cajero',
            'name' => 'CAJERO DE PRUEBA',
            'position' => 'CAJERO',
            'password' => 'cajero',
        ]);
        $cashier->syncRoles('cashier');
        PointOfSale::firstOrCreate(['user_id' => $cashier->id], [
            'branch_id' => $branch->id,
            'default_warehouse_id' => $warehouse->id,
        ]);

        $partner = User::firstOrCreate(['email' => 'partner@mesatek.local'], [
            'username' => 'partner',
            'name' => 'PARTNER DE PRUEBA',
            'position' => 'PARTNER',
            'password' => 'partner',
        ]);
        $partner->syncRoles('partner');
        PointOfSale::firstOrCreate(['user_id' => $partner->id], [
            'branch_id' => $branch->id,
            'default_warehouse_id' => $warehouse->id,
            'multi_branch_sales' => true,
            'start_date' => today(),
        ]);
    }
}
