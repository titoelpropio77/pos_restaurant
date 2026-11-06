<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta / factura (legacy: ven_gral_mysql).
 */
class Sale extends Model
{
    use BelongsToRestaurant;

    protected $table = 'sales';

    protected $fillable = [
        'invoice_ref',
        'branch_id',
        'cash_session_id',
        'order_number',
        'sale_date',
        'login',
        'payment_method',
        'status',
        'voided_by',
        'service_type',
        'shift',
        'customer_id',
        'seller_id',
        'sale_time',
        'exchange_rate',
        'invoice_total',
        'debt_total',
        'payable',
        'pending',
        'voided',
        'closed',
        'invoice_number',
        'real_invoice_number',
        'prepaid_ref',
        'paid_usd',
        'change_usd',
        'paid_bob',
        'change_bob',
        'currency',
        'staff_code',
        'customer_tax_id',
        'customer_id_number',
        'customer_name',
        'auth_key',
        'authorization_number',
        'limit_date',
        'control_code',
        'prepaid_balance',
        'orders_ref',
        'delivered',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    protected function casts(): array
    {
        return [
            'sale_date' => 'date:Y-m-d',
            'exchange_rate' => 'decimal:3',
            'invoice_total' => 'decimal:4',
            'debt_total' => 'decimal:4',
            'paid_usd' => 'decimal:3',
            'change_usd' => 'decimal:3',
            'paid_bob' => 'decimal:3',
            'change_bob' => 'decimal:3',
            'limit_date' => 'date:Y-m-d',
            'prepaid_balance' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function consumptionsCard(): HasMany
    {
        return $this->hasMany(PrepaidCardItem::class);
    }
}
