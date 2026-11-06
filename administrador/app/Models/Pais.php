<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * País (catálogo común a todos los restaurantes).
 */
class Pais extends Model
{
    protected $table = 'paises';

    protected $guarded = ['id'];
}
