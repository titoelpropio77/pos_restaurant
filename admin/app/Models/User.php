<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario de un restaurante, el que ingresa a restaurant_pos.
 *
 * Esta app nunca lo autentica: sólo lo administra. El guard web existe para que Spatie
 * pueda asignarle los roles de restaurante.
 */
class User extends Authenticatable
{
    use HasRoles;

    protected string $guard_name = 'web';

    protected $fillable = ['name', 'username', 'email', 'password', 'position', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtolower(trim($value)));
    }

    protected function username(): Attribute
    {
        return Attribute::set(fn (string $value) => mb_strtolower(trim($value)));
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class);
    }

    public function pointsOfSale(): HasMany
    {
        return $this->hasMany(PointOfSale::class);
    }

    public function pointOfSaleIn(int $restaurantId): ?PointOfSale
    {
        return $this->pointsOfSale()->where('restaurant_id', $restaurantId)->first();
    }
}
