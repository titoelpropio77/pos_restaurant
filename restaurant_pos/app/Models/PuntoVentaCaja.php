<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cajas de un punto de venta.
 */
class PuntoVentaCaja extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'punto_venta_cajas';

    protected $fillable = [
        'punto_venta_id',
        'caja_id',
    ];

    public function puntoVenta(): BelongsTo
    {
        return $this->belongsTo(PuntoVenta::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }
}
