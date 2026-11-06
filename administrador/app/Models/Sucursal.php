<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Local de un restaurante. Cada restaurante tiene exactamente una casa matriz (es_matriz).
 */
class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['es_matriz' => 'boolean'];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function ciudad(): BelongsTo
    {
        return $this->belongsTo(Ciudad::class);
    }

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class);
    }
}
