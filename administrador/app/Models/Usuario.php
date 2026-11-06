<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Hash;

/**
 * Usuario de un restaurante, el que ingresa a restaurant_pos.
 *
 * El POS no distingue mayúsculas: guarda el login y compara la contraseña en mayúsculas,
 * así que aquí deben guardarse igual o el usuario no podría ingresar.
 */
class Usuario extends Model
{
    public const NIVEL_ADMIN = 1;

    public const NIVEL_CAJERO = 2;

    protected $table = 'usuarios';

    protected $fillable = ['nombres', 'usuario', 'password', 'cargo', 'estado', 'nivel', 'rol_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function usuario(): Attribute
    {
        return Attribute::set(fn (string $valor) => mb_strtoupper(trim($valor)));
    }

    protected function password(): Attribute
    {
        return Attribute::set(fn (string $valor) => Hash::make(mb_strtoupper($valor)));
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function puntoVenta(): HasOne
    {
        return $this->hasOne(PuntoVenta::class);
    }

    public static function nivelDe(Rol $rol): int
    {
        return $rol->tiene('panel.acceder') ? self::NIVEL_ADMIN : self::NIVEL_CAJERO;
    }
}
