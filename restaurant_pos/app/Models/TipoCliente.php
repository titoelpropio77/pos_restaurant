<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Tipos de cliente.
 */
class TipoCliente extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'tipos_cliente';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
