<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Centros de costo.
 */
class CentroCosto extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'centros_costo';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
