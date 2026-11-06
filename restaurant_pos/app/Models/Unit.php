<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Unidades de medida.
 */
class Unit extends Model
{
    use BelongsToRestaurant;

    protected $table = 'units';

    protected $fillable = [
        'name',
        'abbreviation',
        'status',
    ];
}
