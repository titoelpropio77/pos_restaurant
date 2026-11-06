<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bitácora de ingresos al POS con tipo de cambio (legacy: inguser_mysql).
 */
class UserLogin extends Model
{
    use BelongsToRestaurant;

    protected $table = 'user_logins';

    protected $fillable = [
        'login_count',
        'user_id',
        'username',
        'login_date',
        'time',
        'exchange_rate',
    ];

    protected function casts(): array
    {
        return [
            'login_date' => 'date:Y-m-d',
            'exchange_rate' => 'decimal:2',
        ];
    }

    public function userModel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
