<?php

namespace App\Models\Concerns;

use App\Models\Restaurant;
use App\Models\Scopes\RestaurantScope;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo de un restaurante: se consulta filtrado y se crea con el restaurante actual.
 *
 * restaurant_id queda fuera de $fillable a propósito, para que nunca se pueda fijar desde un formulario.
 */
trait BelongsToRestaurant
{
    public static function bootBelongsToRestaurant(): void
    {
        static::addGlobalScope(new RestaurantScope);

        static::creating(function (Model $model) {
            $model->restaurant_id ??= Tenant::id();
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
