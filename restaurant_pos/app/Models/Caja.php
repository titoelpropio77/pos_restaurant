<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cajas asociadas a una cuenta contable (legacy: cajacuenta).
 */
class Caja extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cajas';

    protected $fillable = [
        'nombre',
        'cuenta_id',
        'estado',
        'eliminado',
        'moneda',
    ];

    protected function casts(): array
    {
        return [
            'eliminado' => 'boolean',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
