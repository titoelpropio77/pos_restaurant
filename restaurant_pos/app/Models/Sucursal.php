<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sucursal / local del restaurante.
 */
class Sucursal extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'estado',
        'ciudad_id',
        'contador',
        'ip_impresion',
    ];

    protected function casts(): array
    {
        return ['es_matriz' => 'boolean'];
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function dosificaciones(): HasMany
    {
        return $this->hasMany(Dosificacion::class);
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class);
    }

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class);
    }

    public function correlativos(): HasMany
    {
        return $this->hasMany(Correlativo::class);
    }

    public function productosSucursal(): HasMany
    {
        return $this->hasMany(ProductoSucursal::class);
    }
}
