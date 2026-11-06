<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta / factura (legacy: ven_gral_mysql).
 */
class Venta extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'ventas';

    protected $fillable = [
        'factura',
        'sucursal_id',
        'orden',
        'f_venta',
        'login',
        'formapago',
        'estado',
        'eliminpor',
        'salida',
        'turno',
        'cliente_id',
        'vendedor',
        'dhoraven',
        'cam_of',
        'total_fact',
        'tot_deuda',
        'xpagar',
        'pendiente',
        'obser',
        'anulado',
        'cerrado',
        'fortax',
        'tot_des',
        'alfa',
        'numorden',
        'pago_us',
        'cambio_us',
        'pago_bs',
        'cambio_bs',
        'moneda',
        'cod_empl',
        'nit_cli',
        'ci_cli',
        'nom_cli',
        'llave',
        'nroautori',
        'fecha_lim',
        'cifrado',
        'pre_pago',
        'ordenes',
        'entregado',
    ];

    protected function casts(): array
    {
        return [
            'f_venta' => 'date:Y-m-d',
            'cam_of' => 'decimal:3',
            'total_fact' => 'decimal:4',
            'tot_deuda' => 'decimal:4',
            'pago_us' => 'decimal:3',
            'cambio_us' => 'decimal:3',
            'pago_bs' => 'decimal:3',
            'cambio_bs' => 'decimal:3',
            'fecha_lim' => 'date:Y-m-d',
            'pre_pago' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function consumosTarjeta(): HasMany
    {
        return $this->hasMany(TarjetaDetalle::class);
    }
}
