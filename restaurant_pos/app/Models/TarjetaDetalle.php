<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo con tarjeta prepago (legacy: dettarjeta_mysql).
 */
class TarjetaDetalle extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'tarjeta_detalles';

    protected $fillable = [
        'venta_id',
        'sucursal_id',
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

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'cod_prod');
    }
}
