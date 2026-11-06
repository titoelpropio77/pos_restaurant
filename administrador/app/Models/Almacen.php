<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Almacén de una sucursal.
 */
class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $guarded = ['id'];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
