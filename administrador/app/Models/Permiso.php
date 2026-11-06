<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso de un rol. Los protegidos son los que el código de las aplicaciones consulta.
 */
class Permiso extends Model
{
    protected $table = 'permisos';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['protegido' => 'boolean'];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'permiso_rol', 'permiso_id', 'rol_id');
    }
}
