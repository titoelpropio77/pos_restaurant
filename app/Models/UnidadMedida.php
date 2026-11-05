<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Unidades de medida.
 */
class UnidadMedida extends Model
{
    protected $table = 'unidades_medida';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
