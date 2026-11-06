<?php

namespace App\Models\Scopes;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Limita toda consulta al restaurante actual (ver App\Support\Restaurante).
 */
class RestaurantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $restaurantId = Tenant::id();

        if ($restaurantId !== null) {
            $builder->where($model->qualifyColumn('restaurant_id'), $restaurantId);
        } elseif (Tenant::strict()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
