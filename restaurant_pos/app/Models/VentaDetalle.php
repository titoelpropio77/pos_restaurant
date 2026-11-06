<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detalle de venta (legacy: detalles_mysql).
 */
class VentaDetalle extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'orden',
        'item',
        'cod_prod',
        'cantidad',
        'pre_costo',
        'pre_venta',
        'cam_of',
        'f_venta',
        'dhoraven',
        'nom_prod',
        'unidad',
        'pendiente',
        'anulado',
        'presa',
        'grupo',
        'cerrado',
        'turno',
        'familia',
        'unid',
        'salida',
        'fortax',
        'tot_des',
        'factor',
        'agrupa1',
        'agrupa2',
        'agrupa3',
        'cod_grupo',
        'login',
        'ordenes',
        'nota',
        'barcode',
        'indice',
        'deconsumo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'pre_costo' => 'decimal:4',
            'pre_venta' => 'decimal:2',
            'cam_of' => 'decimal:3',
            'f_venta' => 'date:Y-m-d',
            'unid' => 'decimal:4',
            'factor' => 'decimal:5',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'cod_prod');
    }
}
