<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Cuenta del sistema administrador. No tiene relación con los usuarios de los restaurantes.
 */
class Admin extends Authenticatable
{
    use HasRoles;

    protected string $guard_name = 'admin';

    protected $fillable = ['name', 'email', 'password', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'last_login_at' => 'datetime'];
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }
}
