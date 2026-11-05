<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ítem pedido en una mesa abierta (legacy: productos_mesas_mysql).
 */
class MesaPedido extends Model
{
    protected $table = 'mesa_pedidos';

    protected $fillable = [
        'mesa_id',
        'sucursal_id',
        'factura',
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
        'nota',
        'item_eli',
        'nom_empl',
        'deleteado',
        'tiempo',
        'tiempo_ini',
        'tiempo_fin',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'pre_costo' => 'decimal:4',
            'pre_venta' => 'decimal:2',
            'cam_of' => 'decimal:2',
            'f_venta' => 'date:Y-m-d',
            'unid' => 'decimal:3',
            'factor' => 'decimal:5',
        ];
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'cod_prod');
    }

    /**
     * Ítems todavía abiertos en la mesa: no cobrados, no traspasados ni eliminados.
     */
    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereRaw("coalesce(deleteado, '') = ''")
            ->whereRaw("coalesce(item_eli, '') = ''")
            ->whereRaw("coalesce(cerrado, '') = ''");
    }
}
