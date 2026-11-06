<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecera de movimiento de inventario.
 */
class StockMovement extends Model
{
    use BelongsToRestaurant;

    protected $table = 'stock_movements';

    protected $fillable = [
        'date',
        'time',
        'total',
        'type',
        'description',
        'warehouse_id',
        'destination_warehouse_id',
        'status',
        'movement_reason_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'total' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function movementReason(): BelongsTo
    {
        return $this->belongsTo(MovementReason::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }
}
