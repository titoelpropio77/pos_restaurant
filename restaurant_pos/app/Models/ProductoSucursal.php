<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Precio y configuración de un producto en una sucursal.
 */
class ProductoSucursal extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'producto_sucursal';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'categoria_id',
        'sugerencia',
        'precio_venta',
        'precio_venta_normal',
        'precio_venta_mayorista',
        'precio_venta3',
        'precio_venta4',
        'lun',
        'mar',
        'mier',
        'jue',
        'vie',
        'sab',
        'dom',
        'hora_inicio',
        'hora_fin',
        'impcoc',
        'imppunt1',
        'imppunt2',
        'imppunt3',
        'imppunt4',
        'f_vence',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio_venta' => 'decimal:2',
            'precio_venta_normal' => 'decimal:2',
            'precio_venta_mayorista' => 'decimal:2',
            'precio_venta3' => 'decimal:2',
            'precio_venta4' => 'decimal:2',
            'f_vence' => 'date:Y-m-d',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }
}
