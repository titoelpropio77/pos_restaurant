<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuentas bancarias de un punto de venta.
 */
class PuntoVentaCuenta extends Model
{
    protected $table = 'punto_venta_cuentas';

    protected $fillable = [
        'punto_venta_id',
        'cuenta_bancaria_id',
    ];

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class);
    }

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }
}
