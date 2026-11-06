<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * País (catálogo común a todos los restaurantes).
 */
class Country extends Model
{
    protected $table = 'countries';

    protected $guarded = ['id'];
}
