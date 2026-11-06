<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relación producto - grupo de reporte.
 */
class GrupoProducto extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'grupo_producto';

    protected $fillable = [
        'grupo_id',
        'producto_id',
        'cantidad',
        'unidad',
        'factor',
        'nom_prod',
        'cangrupo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'unidad' => 'decimal:3',
            'factor' => 'decimal:2',
            'cangrupo' => 'decimal:3',
        ];
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
