<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Turnos de trabajo.
 */
class Turno extends Model
{
    protected $table = 'turnos';

    protected $fillable = [
        'nombre',
        'minutos_tolerancia',
        'estado',
    ];
}
