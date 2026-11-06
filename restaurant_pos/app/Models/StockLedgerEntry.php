<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kardex de insumos.
 */
class StockLedgerEntry extends Model
{
    use BelongsToRestaurant;

    protected $table = 'stock_ledger';

    protected $fillable = [
        'stock_movement_id',
        'sale_id',
        'supply_id',
        'warehouse_id',
        'supply_name',
        'quantity',
        'movement_date',
        'details',
        'qty_in',
        'qty_out',
        'balance',
        'movement_type',
        'cost_price',
        'average_cost',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'movement_date' => 'date:Y-m-d',
            'qty_in' => 'decimal:4',
            'qty_out' => 'decimal:4',
            'balance' => 'decimal:4',
            'cost_price' => 'decimal:4',
            'average_cost' => 'decimal:4',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }
}
