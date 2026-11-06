<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ciudad (catálogo común a todos los restaurantes).
 */
class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $guarded = ['id'];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }
}
