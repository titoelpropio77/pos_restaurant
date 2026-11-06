<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Turnos de trabajo.
 */
class Turno extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'turnos';

    protected $fillable = [
        'nombre',
        'minutos_tolerancia',
        'estado',
    ];
}
