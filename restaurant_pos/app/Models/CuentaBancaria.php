<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuentas bancarias.
 */
class CuentaBancaria extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cuentas_bancarias';

    protected $fillable = [
        'banco_id',
        'nro_cuenta',
        'tipo_cuenta',
        'estado',
        'razon_social',
        'moneda',
        'cuenta_id',
        'tarjeta_debito',
        'tarjeta_credito',
        'chequera',
    ];

    protected function casts(): array
    {
        return [
            'tarjeta_debito' => 'boolean',
            'tarjeta_credito' => 'boolean',
            'chequera' => 'boolean',
        ];
    }

    public function banco(): BelongsTo
    {
        return $this->belongsTo(Banco::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
