<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Listas de precio.
 */
class ListaPrecio extends Model
{
    protected $table = 'listas_precio';

    protected $fillable = [
        'nombre',
        'moneda_id',
    ];

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }
}
