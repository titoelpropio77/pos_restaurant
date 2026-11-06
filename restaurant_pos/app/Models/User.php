<?php

namespace App\Models;

use App\Models\Scopes\UserRestaurantScope;
use App\Support\Tenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Usuario de un restaurante. Legacy: usuarios_mysql.
 */
class User extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $fillable = ['name', 'username', 'email', 'password', 'position', 'shift', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected static function booted(): void
    {
        static::addGlobalScope(new UserRestaurantScope);

        static::created(function (self $user) {
            if (Tenant::id()) {
                $user->restaurants()->attach(Tenant::id());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Ni el correo ni el código distinguen mayúsculas: se guardan y se comparan en minúsculas. */
    protected function email(): Attribute
    {
        return Attribute::set(fn (string $value) => self::normalize($value));
    }

    protected function username(): Attribute
    {
        return Attribute::set(fn (string $value) => self::normalize($value));
    }

    public static function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class);
    }

    /** Punto de venta en el restaurante actual: PointOfSale ya viene filtrado. */
    public function pointOfSale(): HasOne
    {
        return $this->hasOne(PointOfSale::class);
    }

    /** @return list<int> */
    public function activeRestaurantIds(): array
    {
        return $this->restaurants()->where('status', 'ACTIVE')->orderBy('restaurants.name')
            ->pluck('restaurants.id')->map(fn ($id) => (int) $id)->all();
    }

    /** Restaurante con el que entra: el último donde trabajó, si sigue disponible. */
    public function preferredRestaurantId(array $restaurantIds): ?int
    {
        $last = UserLogin::withoutGlobalScopes()->where('user_id', $this->id)->latest('id')->value('restaurant_id');

        return in_array($last, $restaurantIds, true) ? $last : ($restaurantIds[0] ?? null);
    }

    public function canAccessPanel(): bool
    {
        return $this->can('panel.access');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
