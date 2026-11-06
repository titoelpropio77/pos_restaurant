<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock de un insumo en un almacén.
 */
class WarehouseStock extends Model
{
    use BelongsToRestaurant;

    protected $table = 'warehouse_stocks';

    protected $fillable = [
        'supply_id',
        'warehouse_id',
        'current_stock',
    ];

    protected function casts(): array
    {
        return [
            'current_stock' => 'decimal:4',
        ];
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
