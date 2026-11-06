<?php

namespace App\Models\Scopes;

use App\Support\Restaurante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita toda consulta al restaurante actual (ver App\Support\Restaurante).
 */
class EmpresaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $empresaId = Restaurante::id();

        if ($empresaId !== null) {
            $builder->where($model->qualifyColumn('empresa_id'), $empresaId);
        } elseif (Restaurante::estricto()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
