<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detalle (debe/haber) de un asiento.
 */
class AsientoDetalle extends Model
{
    protected $table = 'asiento_detalles';

    protected $fillable = [
        'asiento_id',
        'cuenta_id',
        'nro_detalle',
        'tipo',
        'monto_sus',
        'monto_bs',
        'centro_costo_id',
    ];

    protected function casts(): array
    {
        return [
            'monto_sus' => 'decimal:2',
            'monto_bs' => 'decimal:2',
        ];
    }

    public function asiento(): BelongsTo
    {
        return $this->belongsTo(Asiento::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }

    public function centroCosto(): BelongsTo
    {
        return $this->belongsTo(CentroCosto::class);
    }
}
