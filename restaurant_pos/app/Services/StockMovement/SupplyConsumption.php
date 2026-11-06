<?php

namespace App\Services\StockMovement;

use App\Exceptions\PosException;
use App\Models\MovementReason;
use App\Models\RecipeItem;
use App\Models\StockLedgerEntry;
use App\Models\StockMovement;
use App\Models\WarehouseStock;

/**
 * Descuenta del almacén los insumos de la receta de los productos vendidos y registra
 * el kardex (legacy: bloque de inventario en facturarController / deTajetaController).
 *
 * Se crea un único movimiento de inventario por documento, sólo si algún producto tiene receta.
 */
class SupplyConsumption
{
    private ?StockMovement $movement_type = null;

    public function __construct(
        private ?int $warehouseId,
        private int $userId,
        private int $documentId,
        private string $date,
        private string $time,
    ) {}

    public function download(int $productId, float $quantitySold): void
    {
        $recipe = RecipeItem::with('supply')->where('product_id', $productId)->get();

        if ($recipe->isEmpty()) {
            return;
        }

        if (! $this->warehouseId) {
            throw new PosException('EL PUNTO DE VENTA NO TIENE ALMACÉN POR DEFECTO');
        }

        $movement_type = $this->movement_type();

        foreach ($recipe as $item) {
            $quantity = $quantitySold * (float) $item->quantity;

            $stock = WarehouseStock::lockForUpdate()->firstOrNew([
                'supply_id' => $item->supply_id,
                'warehouse_id' => $this->warehouseId,
            ]);
            $stock->current_stock = (float) $stock->current_stock - $quantity;
            $stock->save();

            StockLedgerEntry::create([
                'stock_movement_id' => $movement_type->id,
                'sale_id' => $this->documentId,
                'supply_id' => $item->supply_id,
                'warehouse_id' => $this->warehouseId,
                'supply_name' => $item->supply->supply_name,
                'quantity' => $quantity,
                'movement_date' => $this->date,
                'qty_in' => 0,
                'qty_out' => $quantity,
                'balance' => $stock->current_stock,
                'movement_type' => 'VENTA',
                'unit' => $item->supply->measure,
            ]);
        }
    }

    private function movement_type(): StockMovement
    {
        return $this->movement_type ??= StockMovement::create([
            'date' => $this->date,
            'time' => $this->time,
            'total' => 0,
            'type' => 'VENTA',
            'description' => 'MOVIMIENTO GENERADO POR VENTA '.$this->documentId,
            'warehouse_id' => $this->warehouseId,
            'status' => '0',
            'movement_reason_id' => MovementReason::where('name', config('pos.movement_reason_sale'))->value('id'),
            'user_id' => $this->userId,
        ]);
    }
}
