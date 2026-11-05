<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Credenciales del servicio Tigo Money.
 */
class TigoMoneyConfiguracion extends Model
{
    protected $table = 'tigo_money_configuraciones';

    protected $fillable = [
        'llave_identificacion',
        'llave_encriptacion',
        'url_servicio_web',
    ];
}
