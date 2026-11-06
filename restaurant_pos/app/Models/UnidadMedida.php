<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Unidades de medida.
 */
class UnidadMedida extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'unidades_medida';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
