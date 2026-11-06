<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Plan de cuentas.
 */
class CuentaContable extends Model
{
    use PerteneceAEmpresa;
    use SoftDeletes;

    protected $table = 'cuentas_contables';

    protected $fillable = [
        'codigo',
        'padre_id',
        'hijo',
        'nombre',
        'estado',
        'utilizable',
        'moneda_id',
    ];

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function padre(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'padre_id');
    }

    public function hijas(): HasMany
    {
        return $this->hasMany(CuentaContable::class, 'padre_id');
    }
}
