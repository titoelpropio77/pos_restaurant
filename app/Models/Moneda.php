<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Monedas (Bs, USD...).
 */
class Moneda extends Model
{
    protected $table = 'monedas';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
