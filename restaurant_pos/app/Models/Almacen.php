<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Almacenes por sucursal.
 */
class Almacen extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'almacenes';

    protected $fillable = [
        'nombre',
        'sucursal_id',
        'estado',
        'empleado_responsable_id',
        'cuenta_id',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InsumoAlmacen::class);
    }
}
