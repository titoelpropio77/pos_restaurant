<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Clientes.
 */
class Customer extends Model
{
    use BelongsToRestaurant;

    protected $table = 'customers';

    protected $fillable = [
        'customer_code',
        'customer_name',
        'customer_phone',
        'customer_mobile',
        'customer_tax_id',
        'customer_address',
        'customer_email',
        'customer_id_number',
        'birth_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date:Y-m-d',
        ];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
