<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tipos de cliente.
 */
class TipoCliente extends Model
{
    protected $table = 'tipos_cliente';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'estado',
    ];
}
