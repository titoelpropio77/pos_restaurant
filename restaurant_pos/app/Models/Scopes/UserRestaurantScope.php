<?php

namespace App\Models\Scopes;

use App\Support\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Usuarios del restaurante actual. Un usuario puede pertenecer a varios (partner),
 * por eso se filtra por la tabla pivote y no por una columna propia.
 */
class UserRestaurantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $restaurantId = Tenant::id();

        if ($restaurantId !== null) {
            $builder->whereIn($model->qualifyColumn('id'), fn ($query) => $query
                ->select('user_id')
                ->from('restaurant_user')
                ->where('restaurant_id', $restaurantId));
        } elseif (Tenant::strict()) {
            $builder->whereRaw('1 = 0');
        }
    }
}
