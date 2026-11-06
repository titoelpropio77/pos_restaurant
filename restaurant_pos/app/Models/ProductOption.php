<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opciones / modificadores de un producto.
 */
class ProductOption extends Model
{
    use BelongsToRestaurant;

    protected $table = 'product_options';

    protected $fillable = [
        'product_id',
        'product_name',
        'option_name',
        'status',
        'sale_price',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
