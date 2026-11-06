<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos fiscales de una sucursal. Se cargan desde el panel del restaurante.
 */
class Dosificacion extends Model
{
    protected $table = 'dosificaciones';

    protected $guarded = ['id'];
}
