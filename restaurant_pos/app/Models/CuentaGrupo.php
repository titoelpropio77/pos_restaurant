<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agrupación de cuentas.
 */
class CuentaGrupo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'cuenta_grupos';

    protected $fillable = [
        'cuenta_contable_id',
        'descripcion',
    ];

    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class);
    }
}
