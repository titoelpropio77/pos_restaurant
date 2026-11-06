<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol de las cuentas del sistema (guard admin) o de los usuarios de restaurante (guard web).
 */
class Role extends SpatieRole
{
    public const GUARD_ADMIN = 'admin';

    public const GUARD_RESTAURANT = 'web';

    public const GUARDS = [self::GUARD_ADMIN => 'Sistema administrador', self::GUARD_RESTAURANT => 'Restaurantes'];

    public const SUPER_ADMIN = 'superadmin';

    public const PARTNER = 'partner';

    /** Tienen siempre todos los permisos de su guard: quitárselos dejaría al sistema sin dueño. */
    public const FULL = [self::SUPER_ADMIN, self::PARTNER];

    protected function casts(): array
    {
        return ['is_protected' => 'boolean'];
    }

    public function admins(): MorphToMany
    {
        return $this->morphedByMany(Admin::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }

    public function restaurantUsers(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }

    public function isFull(): bool
    {
        return in_array($this->name, self::FULL, true);
    }

    public function scopeForGuard(Builder $query, string $guard): Builder
    {
        return $query->where('guard_name', $guard)->orderBy('id');
    }
}
