<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Cuenta del sistema administrador. No tiene relación con los usuarios de los restaurantes.
 */
class Administrador extends Authenticatable
{
    protected $table = 'administradores';

    protected $fillable = ['nombre', 'correo', 'password', 'rol_id', 'estado'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'ultimo_ingreso' => 'datetime'];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function puede(string $permiso): bool
    {
        return $this->rol->tiene($permiso);
    }

    public function estaActivo(): bool
    {
        return $this->estado === 'ACTIVO';
    }

    public function esSuperadministrador(): bool
    {
        return $this->rol->esTotal();
    }
}
