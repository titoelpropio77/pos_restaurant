<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proveedores.
 */
class Supplier extends Model
{
    use BelongsToRestaurant;

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'website',
        'contact_name',
        'contact_phone',
        'contact_email',
        'city_id',
        'status',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
