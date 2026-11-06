<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sucursal y almacén con que un usuario ingresa al POS.
 */
class PuntoVenta extends Model
{
    protected $table = 'puntos_venta';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['venta_multialmacen' => 'boolean', 'puede_vender' => 'boolean'];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
