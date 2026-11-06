<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Restaurante: cuenta del sistema dueña de sucursales, usuarios y demás datos de negocio.
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
        'nit',
        'telefono',
        'direccion',
        'opciones',
    ];

    protected function casts(): array
    {
        return [
            'vender_sin_stock' => 'boolean',
            'registro_diario' => 'boolean',
            'multilista_precio' => 'boolean',
            'opciones' => 'array',
        ];
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }
}
