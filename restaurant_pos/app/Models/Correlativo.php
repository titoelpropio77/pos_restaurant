<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Numeración de facturas y órdenes por sucursal.
 */
class Correlativo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'correlativos';

    protected $fillable = [
        'tabla',
        'sucursal_id',
        'folio',
        'folior',
        'ordendia',
        'imprimir',
        'multcon',
        'mult',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public static function deVentas(int $sucursalId): ?self
    {
        return static::where('tabla', config('posgourmet.correlativo_ventas'))
            ->where('sucursal_id', $sucursalId)
            ->first();
    }
}
