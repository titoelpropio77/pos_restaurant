<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Personal operativo del POS: meseros, cocina... (legacy: empleados_mysql).
 */
class Personal extends Model
{
    protected $table = 'personal';

    protected $fillable = [
        'cod_empl',
        'nom_empl',
        'dir_empl',
        'ci_empl',
        'cargo',
        'sueld_empl',
        'estado',
        'porcentaje',
        'coloremp',
        'codigo',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'sueld_empl' => 'decimal:2',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
