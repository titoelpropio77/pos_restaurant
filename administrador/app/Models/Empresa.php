<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Restaurante: cuenta del sistema dueña de sucursales, usuarios y demás datos de negocio.
 */
class Empresa extends Model
{
    /** Opciones del POS y su valor inicial en un restaurante nuevo. */
    public const OPCIONES = [
        'salon' => ['Mesas identificadas por nombre del cliente', false],
        'codigo_mesero' => ['Pedir código del mesero al abrir mesa', false],
        'meseros_sin_codigo' => ['No validar el código del mesero', true],
        'cierre_diario' => ['Botón de cierre diario de caja', true],
        'billar' => ['Modo billar (cobro por tiempo)', false],
        'porcentaje_restaurante' => ['Porcentaje del restaurante', false],
        'sin_vista_empleado' => ['Ocultar vista de empleado', true],
        'impresion_nube' => ['Impresión en la nube', false],
        'cambio_fecha' => ['Permitir cambiar la fecha de venta', false],
    ];

    protected $table = 'empresas';

    protected $fillable = ['nombre', 'nit', 'telefono', 'direccion', 'correo', 'web', 'propietario', 'actividad', 'estado', 'opciones'];

    protected function casts(): array
    {
        return ['opciones' => 'array'];
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }

    public function matriz(): HasOne
    {
        return $this->hasOne(Sucursal::class)->where('es_matriz', true);
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public static function opcionesIniciales(): array
    {
        return array_map(fn ($opcion) => $opcion[1], self::OPCIONES);
    }
}
