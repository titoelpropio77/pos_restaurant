<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cuentas de impuestos.
 */
class CuentaImpuesto extends Model
{
    protected $table = 'cuenta_impuestos';

    protected $fillable = [
        'cuenta_contable_id',
        'porcentaje_des',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'porcentaje_des' => 'decimal:4',
        ];
    }

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class);
    }
}
