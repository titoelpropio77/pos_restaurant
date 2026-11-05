<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Asiento contable.
 */
class Asiento extends Model
{
    protected $table = 'asientos';

    protected $fillable = [
        'nro_asiento',
        'glosa',
        'fecha_creacion',
        'fecha_transaccion',
        'cambio_tipo',
        'estado',
        'motivo_anulado',
        'gestion_id',
        'categoria_asiento',
        'usuario_id',
        'documento',
        'total_bs',
        'total_sus',
        'relacionado_a',
        'tipo_asiento',
        'origen',
    ];

    protected function casts(): array
    {
        return [
            'fecha_creacion' => 'datetime',
            'fecha_transaccion' => 'datetime',
            'cambio_tipo' => 'decimal:2',
            'total_bs' => 'decimal:2',
            'total_sus' => 'decimal:2',
        ];
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(AsientoDetalle::class);
    }
}
