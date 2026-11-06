<?php

namespace App\Services\StockMovement;

use App\Exceptions\PosException;
use App\Models\StockLedgerEntry;
use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

/**
 * Movimientos manuales de inventario con su kardex.
 *
 *  - COMPRA:   ingreso al almacén; recalcula el costo promedio ponderado (PPM) del insumo.
 *  - SALIDA:   merma, consumo interno o baja; no permite dejar stock negativo.
 *  - TRASPASO: del almacén origen al destino (dos registros de kardex).
 *  - AJUSTE:   la cantidad indicada es el stock contado; se registra la diferencia.
 */
class StockMovementService
{
    public const TYPES = [
        'COMPRA' => 'Compra / ingreso',
        'SALIDA' => 'Salida / merma',
        'TRASPASO' => 'Traspaso entre almacenes',
        'AJUSTE' => 'Ajuste por conteo físico',
    ];

    /**
     * @param  array{tipo: string, fecha: string, almacen_id: int, almacen_destino_id?: int|null, motivo_movimiento_id?: int|null, glosa?: string|null, lineas: array<int, array{insumo_id: int, cantidad: float, costo?: float|null}>}  $datos
     */
    public function registrar(array $data, User $user): StockMovement
    {
        return DB::transaction(function () use ($data, $user) {
            $type = $data['type'];

            $movement_type = StockMovement::create([
                'date' => $data['date'],
                'time' => now()->format('H:i'),
                'total' => 0,
                'type' => $type,
                'description' => $data['description'] ?? null,
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => $type === 'TRASPASO' ? $data['destination_warehouse_id'] : null,
                'status' => 'ACTIVE',
                'movement_reason_id' => $data['movement_reason_id'] ?? null,
                'user_id' => $user->id,
            ]);

            $total = 0;
            foreach ($data['lines'] as $line) {
                $supply = Supply::lockForUpdate()->findOrFail($line['supply_id']);
                $quantity = (float) $line['quantity'];

                $total += match ($type) {
                    'COMPRA' => $this->purchase($movement_type, $supply, $quantity, (float) ($line['cost'] ?? 0)),
                    'SALIDA' => $this->qty_out($movement_type, $supply, $data['warehouse_id'], $quantity, 'SALIDA'),
                    'TRASPASO' => $this->transfer($movement_type, $supply, $data['warehouse_id'], $data['destination_warehouse_id'], $quantity),
                    'AJUSTE' => $this->adjustment($movement_type, $supply, $quantity),
                };
            }

            $movement_type->update(['total' => round($total, 4)]);

            return $movement_type;
        });
    }

    private function purchase(StockMovement $movement_type, Supply $supply, float $quantity, float $cost): float
    {
        $stock = (float) WarehouseStock::where('supply_id', $supply->id)->sum('current_stock');
        $ppmPrevious = (float) ($supply->average_cost ?? $supply->cost_price ?? 0);
        $average_cost = $stock + $quantity > 0 && $stock > 0
            ? (($stock * $ppmPrevious) + ($quantity * $cost)) / ($stock + $quantity)
            : $cost;

        $supply->update(['average_cost' => round($average_cost, 4), 'cost_price' => $cost, 'last_purchase_price' => $cost]);

        $balance = $this->move($supply->id, $movement_type->warehouse_id, $quantity);
        $this->kardex($movement_type, $supply, $movement_type->warehouse_id, $quantity, 0, $balance, 'COMPRA', $cost, $average_cost);

        return $quantity * $cost;
    }

    private function qty_out(StockMovement $movement_type, Supply $supply, int $warehouseId, float $quantity, string $concept): float
    {
        $balance = $this->move($supply->id, $warehouseId, -$quantity);
        $this->kardex($movement_type, $supply, $warehouseId, 0, $quantity, $balance, $concept);

        return $quantity * $this->cost($supply);
    }

    private function transfer(StockMovement $movement_type, Supply $supply, int $source, int $destination, float $quantity): float
    {
        $this->qty_out($movement_type, $supply, $source, $quantity, 'TRASP-SAL');
        $balance = $this->move($supply->id, $destination, $quantity);
        $this->kardex($movement_type, $supply, $destination, $quantity, 0, $balance, 'TRASP-ENT');

        return $quantity * $this->cost($supply);
    }

    private function adjustment(StockMovement $movement_type, Supply $supply, float $cash): float
    {
        $current = (float) WarehouseStock::where(['supply_id' => $supply->id, 'warehouse_id' => $movement_type->warehouse_id])->value('current_stock');
        $difference = $cash - $current;

        if ($difference == 0) {
            return 0;
        }

        $balance = $this->move($supply->id, $movement_type->warehouse_id, $difference, allowNegative: true);
        $this->kardex($movement_type, $supply, $movement_type->warehouse_id, max($difference, 0), max(-$difference, 0), $balance, 'AJUSTE');

        return abs($difference) * $this->cost($supply);
    }

    /**
     * Suma (o resta) stock en el almacén y devuelve el saldo resultante.
     */
    private function move(int $supplyId, int $warehouseId, float $quantity, bool $allowNegative = false): float
    {
        $stock = WarehouseStock::lockForUpdate()->firstOrNew(['supply_id' => $supplyId, 'warehouse_id' => $warehouseId]);
        $is_new = (float) $stock->current_stock + $quantity;

        if ($is_new < 0 && ! $allowNegative) {
            $name = Supply::find($supplyId)?->supply_name;
            throw new PosException("Stock insuficiente de {$name}: hay ".rtrim(rtrim(number_format((float) $stock->current_stock, 3), '0'), '.').' y se quiere sacar '.abs($quantity).'.');
        }

        $stock->current_stock = $is_new;
        $stock->save();

        return $is_new;
    }

    private function kardex(StockMovement $movement_type, Supply $supply, int $warehouseId, float $qty_in, float $qty_out, float $balance, string $concept, ?float $cost = null, ?float $average_cost = null): void
    {
        StockLedgerEntry::create([
            'stock_movement_id' => $movement_type->id,
            'supply_id' => $supply->id,
            'warehouse_id' => $warehouseId,
            'supply_name' => $supply->supply_name,
            'quantity' => $qty_in ?: $qty_out,
            'movement_date' => $movement_type->date,
            'details' => mb_substr((string) $movement_type->description, 0, 60),
            'qty_in' => $qty_in,
            'qty_out' => $qty_out,
            'balance' => $balance,
            'movement_type' => $concept,
            'cost_price' => $cost ?? $this->cost($supply),
            'average_cost' => $average_cost ?? $supply->average_cost,
            'unit' => $supply->measure,
        ]);
    }

    private function cost(Supply $supply): float
    {
        return (float) ($supply->average_cost ?? $supply->cost_price ?? 0);
    }
}
