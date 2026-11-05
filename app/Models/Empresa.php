<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Empresa propietaria de las sucursales.
 */
class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nombre',
        'imagen',
        'web',
        'correo',
        'propietario',
        'actividad',
        'estado',
        'vender_sin_stock',
        'registro_diario',
        'multilista_precio',
    ];

    protected function casts(): array
    {
        return [
            'vender_sin_stock' => 'boolean',
            'registro_diario' => 'boolean',
            'multilista_precio' => 'boolean',
        ];
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }
}
