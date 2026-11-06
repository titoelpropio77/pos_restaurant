<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de lo que hacen las cuentas del sistema.
 */
class Auditoria extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria';

    protected $guarded = ['id'];

    public function administrador(): BelongsTo
    {
        return $this->belongsTo(Administrador::class);
    }

    public static function registrar(string $accion, string $entidad, ?int $entidadId, string $descripcion): void
    {
        static::create([
            'administrador_id' => auth()->id(),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidad_id' => $entidadId,
            'descripcion' => mb_substr($descripcion, 0, 250),
            'ip' => request()->ip(),
        ]);
    }
}
