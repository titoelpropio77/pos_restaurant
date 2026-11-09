<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mesa del salón.
 */
class DiningTable extends Model
{
    use BelongsToRestaurant;

    protected $table = 'dining_tables';

    protected $fillable = [
        'branch_id',
        'table_number',
        'status',
        'occupied',
        'order_number',
        'color',
        'processing',
        'deleted_flag',
        'people_count',
        'ticket_count',
        'customer_name',
        'waiter_name',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TableOrder::class);
    }

    /**
     * Deja la mesa libre para el siguiente uso (legacy: MESAS_MYSQL::habilitarMesa).
     */
    public function release(): void
    {
        $this->update([
            'processing' => '',
            'occupied' => '',
            'people_count' => '',
            'ticket_count' => 0,
            'customer_name' => '',
            'color' => '',
            'waiter_name' => '',
        ]);
    }

    public function isOccupied(): bool
    {
        return ($this->occupied ?? '') !== '';
    }
}
