<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesión de caja de un punto de venta: del monto inicial al efectivo contado al cerrar.
 */
class CashSession extends Model
{
    use BelongsToRestaurant;

    public const OPEN = 'OPEN';

    public const CLOSED = 'CLOSED';

    protected $table = 'cash_sessions';

    protected $fillable = [
        'branch_id',
        'point_of_sale_id',
        'opened_by',
        'opened_at',
        'opening_amount',
        'closed_by',
        'closed_at',
        'expected_cash',
        'counted_cash',
        'difference',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_amount' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::OPEN);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::CLOSED);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function pointOfSale(): BelongsTo
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
