<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Empleados (RRHH).
 */
class Empleado extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'empleados';

    protected $fillable = [
        'nombre',
        'fecha_nacimiento',
        'genero',
        'telefono',
        'celular',
        'doc_identidad',
        'correo_electronico',
        'cargo_id',
        'turno_id',
        'estado',
        'comision',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date:Y-m-d',
            'comision' => 'decimal:2',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }
}
