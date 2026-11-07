<?php

namespace App\Services\Products;

use App\Http\Controllers\Admin\ProductController;
use App\Models\Product;

/**
 * Disponibilidad de un producto por sucursal (branch_products): precio, días, horario e impresoras.
 * La usan el formulario de productos y la carga masiva.
 */
class BranchSettings
{
    /**
     * @param  array<int, array{enabled?: bool, sale_price?: mixed, wholesale_price?: mixed, start_time?: ?string,
     *                          end_time?: ?string, expires_on?: ?string, days?: string[], printers?: string[]}>  $branches
     *                                                                                                                   branch_id => configuración
     */
    public static function save(Product $product, array $branches): void
    {
        foreach ($branches as $branchId => $config) {
            $existing = $product->branches()->where('branch_id', $branchId)->first();

            if (empty($config['enabled'])) {
                $existing?->delete();

                continue;
            }

            $days = $config['days'] ?? [];
            $printers = $config['printers'] ?? [];
            $price = ($config['sale_price'] ?? '') !== '' && $config['sale_price'] !== null ? $config['sale_price'] : $product->sale_price;

            $values = [
                'category_id' => $product->category_id,
                'sale_price' => $price,
                'regular_price' => $price,
                'wholesale_price' => $config['wholesale_price'] ?? null,
                'start_time' => ($config['start_time'] ?? null) ?: '00:00',
                'end_time' => ($config['end_time'] ?? null) ?: '00:00',
                'expires_on' => ($config['expires_on'] ?? null) ?: '2999-12-29',
            ];
            foreach (array_keys(ProductController::DAYS) as $day_code) {
                $values[$day_code] = in_array($day_code, $days, true) ? 'x' : '';
            }
            foreach (array_keys(ProductController::PRINTERS) as $printer) {
                $values[$printer] = in_array($printer, $printers, true) ? 'x' : '';
            }

            $product->branches()->updateOrCreate(['branch_id' => $branchId], $values);
        }
    }
}
