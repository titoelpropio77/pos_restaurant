<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Módulos del sistema.
 */
class Modulo extends Model
{
    protected $table = 'modulos';

    protected $fillable = [
        'nombre',
        'estado',
    ];
}
