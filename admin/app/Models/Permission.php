<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Permiso de un rol. Los protegidos son los que el código de las aplicaciones consulta.
 */
class Permission extends SpatiePermission
{
    protected function casts(): array
    {
        return ['is_protected' => 'boolean'];
    }
}
