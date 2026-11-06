<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rol de las cuentas del sistema (SISTEMA) o de los usuarios de restaurante (RESTAURANTE).
 */
class Rol extends Model
{
    public const SISTEMA = 'SISTEMA';

    public const RESTAURANTE = 'RESTAURANTE';

    public const AMBITOS = [self::SISTEMA => 'Sistema administrador', self::RESTAURANTE => 'Restaurantes'];

    /** Tienen siempre todos los permisos de su ámbito: quitárselos dejaría al sistema sin dueño. */
    public const TOTALES = ['Superadministrador', 'Dueño'];

    protected $table = 'roles';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['protegido' => 'boolean'];
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'permiso_rol', 'rol_id', 'permiso_id');
    }

    public function administradores(): HasMany
    {
        return $this->hasMany(Administrador::class, 'rol_id');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function esTotal(): bool
    {
        return $this->protegido && in_array($this->nombre, self::TOTALES, true);
    }

    public function tiene(string $clave): bool
    {
        return $this->permisos->contains('clave', $clave);
    }

    public static function delAmbito(string $ambito)
    {
        return static::where('ambito', $ambito)->orderBy('id');
    }
}
