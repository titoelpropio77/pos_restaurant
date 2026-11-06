<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Permiso de un rol. Se administra desde la app administrador.
 */
class Permiso extends Model
{
    protected $table = 'permisos';

    protected $guarded = ['id'];
}
