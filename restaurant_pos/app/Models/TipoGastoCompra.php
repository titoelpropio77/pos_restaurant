<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipos de gasto / compra.
 */
class TipoGastoCompra extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'tipos_gasto_compra';

    protected $fillable = [
        'nombre',
        'cuenta_id',
        'estado',
    ];

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
