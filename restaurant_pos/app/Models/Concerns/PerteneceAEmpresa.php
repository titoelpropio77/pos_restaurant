<?php

namespace App\Models\Concerns;

use App\Models\Empresa;
use App\Models\Scopes\EmpresaScope;
use App\Support\Restaurante;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de un restaurante: se consulta filtrado y se crea con el restaurante actual.
 *
 * empresa_id queda fuera de $fillable a propósito, para que nunca se pueda fijar desde un formulario.
 */
trait PerteneceAEmpresa
{
    public static function bootPerteneceAEmpresa(): void
    {
        static::addGlobalScope(new EmpresaScope);

        static::creating(function (Model $modelo) {
            $modelo->empresa_id ??= Restaurante::id();
        });
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
