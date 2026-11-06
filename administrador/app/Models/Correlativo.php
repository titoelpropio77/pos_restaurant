<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Numeración de ventas de una sucursal.
 */
class Correlativo extends Model
{
    protected $table = 'correlativos';

    protected $guarded = ['id'];
}
