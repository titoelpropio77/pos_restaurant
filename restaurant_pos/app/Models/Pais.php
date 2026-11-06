<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de países.
 */
class Pais extends Model
{
    protected $table = 'paises';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function ciudades(): HasMany
    {
        return $this->hasMany(Ciudad::class);
    }
}
