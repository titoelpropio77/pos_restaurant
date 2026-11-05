<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema (cajero / administrador). Legacy: usuarios_mysql.
 */
class Usuario extends Authenticatable
{
    use Notifiable;

    public const NIVEL_ADMIN = 1;

    public const NIVEL_CAJERO = 2;

    protected $table = 'usuarios';

    protected $fillable = [
        'coduser',
        'nombres',
        'cargo',
        'usuario',
        'password',
        'estado',
        'nivel',
        'turno',
        'horain',
        'horasal',
        'manprods',
        'maninven',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'nivel' => 'integer',
        ];
    }

    /** El login se guarda siempre en mayúsculas, como en el sistema original. */
    public function setUsuarioAttribute(string $value): void
    {
        $this->attributes['usuario'] = mb_strtoupper(trim($value));
    }

    public function puntoVenta(): HasOne
    {
        return $this->hasOne(PuntoVenta::class);
    }

    public function esAdministrador(): bool
    {
        return $this->nivel === self::NIVEL_ADMIN;
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'ACTIVO';
    }
}
