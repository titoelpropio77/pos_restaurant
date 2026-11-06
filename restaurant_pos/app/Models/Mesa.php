<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesa del salón.
 */
class Mesa extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'mesas';

    protected $fillable = [
        'sucursal_id',
        'nro_mesa',
        'estado',
        'uso',
        'orden',
        'colores',
        'procesando',
        'deleteado',
        'nropersona',
        'nroorden',
        'nom_cli',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(MesaPedido::class);
    }

    /**
     * Deja la mesa libre para el siguiente uso (legacy: MESAS_MYSQL::habilitarMesa).
     */
    public function liberar(): void
    {
        $this->update([
            'procesando' => '',
            'uso' => '',
            'nropersona' => '',
            'nroorden' => 0,
            'nom_cli' => '',
            'colores' => '',
        ]);
    }

    public function estaOcupada(): bool
    {
        return ($this->uso ?? '') !== '';
    }
}
