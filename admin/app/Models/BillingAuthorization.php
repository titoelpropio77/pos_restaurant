<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos fiscales de una sucursal. Se cargan desde el panel del restaurante.
 */
class BillingAuthorization extends Model
{
    protected $table = 'billing_authorizations';

    protected $guarded = ['id'];
}
