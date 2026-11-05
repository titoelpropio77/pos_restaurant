<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Centros de costo.
 */
class CentroCosto extends Model
{
    protected $table = 'centros_costo';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
