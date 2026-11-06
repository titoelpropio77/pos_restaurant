<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;

/**
 * IPs registradas por sucursal.
 */
class IpSucursal extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'ip_sucursales';

    protected $fillable = [
        'ip_sucursal',
        'nombre_sucursal',
    ];
}
