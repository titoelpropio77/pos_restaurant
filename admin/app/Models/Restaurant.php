<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Restaurante: cuenta del sistema dueña de sucursales, usuarios y demás datos de negocio.
 */
class Restaurant extends Model
{
    /** Opciones del POS y su valor inicial en un restaurante nuevo. */
    public const OPTIONS = [
        'dining_room' => ['Mesas identificadas por nombre del cliente', false],
        'code_waiter' => ['Pedir código del mesero al abrir mesa', false],
        'waiters_without_code' => ['No validar el código del mesero', true],
        'daily_close' => ['Exigir apertura y cierre de caja', true],
        'billiards' => ['Modo billar (cobro por tiempo)', false],
        'percentage_restaurant' => ['Porcentaje del restaurante', false],
        'without_view_employee' => ['Ocultar vista de empleado', true],
        'print_cloud' => ['Impresión en la nube', false],
        'change_date' => ['Permitir cambiar la fecha de venta', false],
        'billing' => ['Facturación SIN (exige dosificación vigente para vender)', true],
    ];

    protected $table = 'restaurants';

    /** Formatos y tamaño máximo (KB) del logo; se guarda en la BD como data URI. */
    public const LOGO_MIMES = ['png', 'jpg', 'jpeg', 'webp'];

    public const LOGO_MAX_KB = 1024;

    /** Paleta sugerida para el fondo de la tienda (también se admite cualquier color #RRGGBB). */
    public const BACKGROUND_PALETTE = [
        '#FAF7F2' => 'Crema',
        '#FFFFFF' => 'Blanco',
        '#F1F5F9' => 'Gris claro',
        '#DCEBF7' => 'Celeste',
        '#E3F2EA' => 'Menta',
        '#FBE9D0' => 'Durazno',
        '#E8961C' => 'Ámbar',
        '#C8553D' => 'Terracota',
        '#8C2F39' => 'Vino',
        '#2E7D5B' => 'Verde',
        '#4A5468' => 'Gris pizarra',
        '#14213D' => 'Azul noche',
    ];

    protected $fillable = ['name', 'tax_id', 'phone', 'address', 'email', 'web', 'activity', 'status', 'options', 'logo', 'background_color'];

    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function main(): HasOne
    {
        return $this->hasOne(Branch::class)->where('is_main', true);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function partners(): BelongsToMany
    {
        return $this->users()->whereHas('roles', fn ($query) => $query->where('name', Role::PARTNER));
    }

    public static function optionsInitial(): array
    {
        return array_map(fn ($option_name) => $option_name[1], self::OPTIONS);
    }
}
