<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * Cargos del personal.
 */
class Cargo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cargos';

    protected $fillable = [
        'nombre',
        'estado',
    ];
}
