<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sucursal y almacén con que un usuario ingresa al POS.
 */
class PointOfSale extends Model
{
    protected $table = 'points_of_sale';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['multi_branch_sales' => 'boolean', 'can_sell' => 'boolean'];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
