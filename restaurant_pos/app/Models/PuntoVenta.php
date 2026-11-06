<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Asignación de un usuario a una sucursal/almacén.
 */
class PuntoVenta extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'puntos_venta';

    protected $fillable = [
        'sucursal_id',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'venta_multialmacen',
        'almacen_por_defecto_id',
        'puede_vender',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
            'venta_multialmacen' => 'boolean',
            'puede_vender' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function almacenPorDefecto(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_por_defecto_id');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(PuntoVentaCaja::class);
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(PuntoVentaCuenta::class);
    }
}
