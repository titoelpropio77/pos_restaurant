<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio y configuración de un producto en una sucursal.
 */
class BranchProduct extends Model
{
    use BelongsToRestaurant;

    protected $table = 'branch_products';

    protected $fillable = [
        'product_id',
        'branch_id',
        'category_id',
        'suggestion',
        'sale_price',
        'regular_price',
        'wholesale_price',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat',
        'sun',
        'start_time',
        'end_time',
        'print_kitchen',
        'print_station_1',
        'print_station_2',
        'print_station_3',
        'print_station_4',
        'expires_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'expires_on' => 'date:Y-m-d',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
