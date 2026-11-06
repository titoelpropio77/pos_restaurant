<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ciudades por país.
 */
class Ciudad extends Model
{
    protected $table = 'ciudades';

    protected $fillable = [
        'nombre',
        'pais_id',
    ];

    public function pais(): BelongsTo
    {
        return $this->belongsTo(Pais::class);
    }
}
