<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opciones / modificadores de un producto.
 */
class ProductoOpcion extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'producto_opciones';

    protected $fillable = [
        'producto_id',
        'nom_prod',
        'opcion',
        'estado',
        'pre_venta',
    ];

    protected function casts(): array
    {
        return [
            'pre_venta' => 'decimal:2',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
