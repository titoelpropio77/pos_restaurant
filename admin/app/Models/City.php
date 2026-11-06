<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ciudad (catálogo común a todos los restaurantes).
 */
class City extends Model
{
    protected $table = 'cities';

    protected $guarded = ['id'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
