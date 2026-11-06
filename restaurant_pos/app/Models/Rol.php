<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Rol de los usuarios de restaurante (ámbito RESTAURANTE). Se administra desde la app administrador.
 */
class Rol extends Model
{
    public const AMBITO_RESTAURANTE = 'RESTAURANTE';

    protected $table = 'roles';

    protected $guarded = ['id'];

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'permiso_rol', 'rol_id', 'permiso_id');
    }

    public static function delRestaurante()
    {
        return static::where('ambito', self::AMBITO_RESTAURANTE);
    }

    /** @return list<string> */
    public function claves(): array
    {
        return $this->permisos->pluck('clave')->all();
    }
}
