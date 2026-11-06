<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Rol de los usuarios de restaurante (guard web). Se administra desde la app admin.
 */
class Role extends SpatieRole
{
    public function scopeForRestaurants(Builder $query): Builder
    {
        return $query->where('guard_name', 'web');
    }
}
