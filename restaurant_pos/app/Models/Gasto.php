<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de caja: egreso (0) o ingreso (1) (legacy: gastos_mysql).
 */
class Gasto extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'gastos';

    protected $fillable = [
        'sucursal_id',
        'nrogasto',
        'fechagasto',
        'entregadoa',
        'concepto',
        'horagasto',
        'login',
        'cam_of',
        'monto',
        'turno',
        'estado',
        'tipotransaccion',
        'nrofactura',
        'fecha_factura',
        'nit_proveedor',
        'nro_autorizacion',
        'codigo_control',
        'con_factura',
        'tipo_cambio',
        'impuesto',
        'credito_fiscal',
        'saliocaja',
    ];

    protected function casts(): array
    {
        return [
            'fechagasto' => 'date:Y-m-d',
            'cam_of' => 'decimal:3',
            'monto' => 'decimal:3',
            'fecha_factura' => 'date:Y-m-d',
            'tipo_cambio' => 'decimal:2',
            'credito_fiscal' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
