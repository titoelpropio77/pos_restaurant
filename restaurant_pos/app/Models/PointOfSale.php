<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Asignación de un usuario a una sucursal/almacén.
 */
class PointOfSale extends Model
{
    use BelongsToRestaurant;

    protected $table = 'points_of_sale';

    protected $fillable = [
        'branch_id',
        'user_id',
        'start_date',
        'end_date',
        'multi_branch_sales',
        'default_warehouse_id',
        'can_sell',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'multi_branch_sales' => 'boolean',
            'can_sell' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouseDefault(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }
}
