<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Insumos / materia prima.
 */
class Supply extends Model
{
    use BelongsToRestaurant;

    protected $table = 'supplies';

    protected $fillable = [
        'supply_code',
        'supply_name',
        'min_stock',
        'current_stock',
        'measure',
        'status',
        'product_code',
        'unit',
        'cost_price',
        'average_cost',
        'last_purchase_price',
    ];

    protected function casts(): array
    {
        return [
            'min_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'unit' => 'decimal:3',
            'cost_price' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'last_purchase_price' => 'decimal:4',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(StockLedgerEntry::class);
    }

    public function recipes(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }
}
