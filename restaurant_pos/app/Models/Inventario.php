<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabecera de movimiento de inventario.
 */
class Inventario extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'inventarios';

    protected $fillable = [
        'fecha',
        'hora',
        'importe',
        'tipo',
        'glosa',
        'almacen_id',
        'almacen_destino_id',
        'estado',
        'motivo_movimiento_id',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date:Y-m-d',
            'importe' => 'decimal:4',
        ];
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function motivoMovimiento(): BelongsTo
    {
        return $this->belongsTo(MotivoMovimiento::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(KardexInsumo::class);
    }
}
