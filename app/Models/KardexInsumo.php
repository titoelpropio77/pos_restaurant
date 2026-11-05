<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kardex de insumos.
 */
class KardexInsumo extends Model
{
    protected $table = 'kardex_insumos';

    protected $fillable = [
        'inventario_id',
        'venta_id',
        'insumo_id',
        'almacen_id',
        'nom_insumo',
        'cantidad',
        'fecha_mov',
        'pormenores',
        'entrada',
        'salida',
        'saldo',
        'movimiento',
        'recepcion',
        'perdida',
        'pre_costo',
        'ppm',
        'unidad',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:4',
            'fecha_mov' => 'date:Y-m-d',
            'entrada' => 'decimal:4',
            'salida' => 'decimal:4',
            'saldo' => 'decimal:4',
            'recepcion' => 'decimal:3',
            'perdida' => 'decimal:3',
            'pre_costo' => 'decimal:4',
            'ppm' => 'decimal:4',
        ];
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class);
    }
}
