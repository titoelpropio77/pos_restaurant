<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * IPs registradas por sucursal.
 */
class IpSucursal extends Model
{
    protected $table = 'ip_sucursales';

    protected $fillable = [
        'ip_sucursal',
        'nombre_sucursal',
    ];
}
