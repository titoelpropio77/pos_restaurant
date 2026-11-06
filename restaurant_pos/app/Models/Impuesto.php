<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Impuestos (IVA, IT...).
 */
class Impuesto extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'impuestos';

    protected $fillable = [
        'nombre',
        'cuenta_id',
        'valor',
        'eliminado',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
            'eliminado' => 'boolean',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_id');
    }
}
