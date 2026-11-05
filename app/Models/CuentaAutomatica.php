<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cuentas usadas en asientos automáticos.
 */
class CuentaAutomatica extends Model
{
    use SoftDeletes;

    protected $table = 'cuentas_automaticas';

    protected $fillable = [
        'nombre',
        'cuenta_id',
        'estado',
        'modulo',
        'tipo_parametro',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
