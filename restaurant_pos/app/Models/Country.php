<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de países.
 */
class Country extends Model
{
    protected $table = 'countries';

    protected $fillable = [
        'name',
        'status',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
