<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Moneda (catálogo común a todos los restaurantes).
 */
class Moneda extends Model
{
    protected $table = 'monedas';

    protected $guarded = ['id'];
}
