<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cargos del personal.
 */
class Cargo extends Model
{
    protected $table = 'cargos';

    protected $fillable = [
        'nombre',
        'estado',
    ];
}
