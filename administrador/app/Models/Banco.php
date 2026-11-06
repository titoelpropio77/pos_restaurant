<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Banco (catálogo común a todos los restaurantes).
 */
class Banco extends Model
{
    protected $table = 'bancos';

    protected $guarded = ['id'];
}
