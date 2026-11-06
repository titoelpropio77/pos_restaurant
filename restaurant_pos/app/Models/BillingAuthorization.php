<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Datos fiscales y de dosificación SIN por sucursal (legacy: empresa_mysql).
 */
class BillingAuthorization extends Model
{
    use BelongsToRestaurant;

    protected $table = 'billing_authorizations';

    protected $fillable = [
        'branch_id',
        'business_name',
        'owner_name',
        'city_country',
        'tax_id',
        'logo',
        'currency',
        'branch',
        'business_address',
        'first_invoice_number',
        'current_invoice',
        'current_real_invoice',
        'business_phone',
        'prepaid_ref',
        'authorization_date',
        'morning_start',
        'morning_end',
        'inactive',
        'auth_key',
        'authorization_number',
        'limit_date',
        'diningTable',
        'takeaway',
        'daily_order',
        'ip',
        'legend_1',
        'legend_2',
    ];

    protected function casts(): array
    {
        return [
            'authorization_date' => 'date:Y-m-d',
            'limit_date' => 'date:Y-m-d',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Última dosificación registrada para la sucursal (legacy: EMPRESA_MYSQL::obtenerUltimo).
     */
    public static function currentFor(int $branchId): ?self
    {
        return static::where('branch_id', $branchId)->latest('id')->first();
    }

    /**
     * Días que faltan para la fecha límite de emisión (negativo si ya venció).
     */
    public function daysUntilExpiry(DateTimeInterface|string $date): ?int
    {
        if (! $this->limit_date) {
            return null;
        }

        return (int) Carbon::parse($date)->startOfDay()
            ->diffInDays(Carbon::parse($this->limit_date)->startOfDay(), false);
    }
}
