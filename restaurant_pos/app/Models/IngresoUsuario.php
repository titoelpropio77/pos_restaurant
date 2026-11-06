<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de ingresos al POS con tipo de cambio (legacy: inguser_mysql).
 */
class IngresoUsuario extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'ingresos_usuario';

    protected $fillable = [
        'ingresos',
        'usuario_id',
        'usuario',
        'vfecha',
        'hora',
        'cam_of',
    ];

    protected function casts(): array
    {
        return [
            'vfecha' => 'date:Y-m-d',
            'cam_of' => 'decimal:2',
        ];
    }

    public function usuarioModelo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
