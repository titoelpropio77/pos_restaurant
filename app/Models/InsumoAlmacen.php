<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock de un insumo en un almacén.
 */
class InsumoAlmacen extends Model
{
    protected $table = 'insumo_almacen';

    protected $fillable = [
        'insumo_id',
        'almacen_id',
        'stock_actual',
    ];

    protected function casts(): array
    {
        return [
            'stock_actual' => 'decimal:4',
        ];
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }
}
