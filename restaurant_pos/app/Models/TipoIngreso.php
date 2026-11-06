<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tipos de ingreso de caja.
 */
class TipoIngreso extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'tipos_ingreso';

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
