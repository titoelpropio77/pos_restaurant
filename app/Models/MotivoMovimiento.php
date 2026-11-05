<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Motivos de movimiento de inventario.
 */
class MotivoMovimiento extends Model
{
    protected $table = 'motivos_movimiento';

    protected $fillable = [
        'nombre',
        'estado',
        'cuenta_id',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
