<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de lo que hacen las cuentas del sistema.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_logs';

    protected $guarded = ['id'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public static function register(string $action, string $entity, ?int $entityId, string $description): void
    {
        static::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'description' => mb_substr($description, 0, 250),
            'ip' => request()->ip(),
        ]);
    }
}
