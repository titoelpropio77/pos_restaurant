<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sucursal / local del restaurante.
 */
class Branch extends Model
{
    use BelongsToRestaurant;

    protected $table = 'branches';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'status',
        'city_id',
        'counter',
        'print_ip',
    ];

    protected function casts(): array
    {
        return ['is_main' => 'boolean'];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(BillingAuthorization::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class);
    }

    public function sequences(): HasMany
    {
        return $this->hasMany(InvoiceSequence::class);
    }

    public function productsBranch(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }
}
