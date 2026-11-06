<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Usuario del sistema (cajero / administrador). Legacy: usuarios_mysql.
 */
class Usuario extends Authenticatable
{
    use Notifiable, PerteneceAEmpresa;

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
        'rol_id',
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

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    /**
     * Permiso del rol asignado. Un usuario sin rol conserva el acceso de su nivel legacy:
     * el administrador todo y el cajero sólo vender.
     */
    public function puede(string $permiso): bool
    {
        if ($this->rol_id) {
            return in_array($permiso, $this->rol->claves(), true);
        }

        return $this->nivel === self::NIVEL_ADMIN || $permiso === 'pos.vender';
    }

    public function esAdministrador(): bool
    {
        return $this->puede('panel.acceder');
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'ACTIVO';
    }
}
