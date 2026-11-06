<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Movimiento de caja: egreso (0) o ingreso (1) (legacy: gastos_mysql).
 */
class Expense extends Model
{
    use BelongsToRestaurant;

    protected $table = 'expenses';

    protected $fillable = [
        'branch_id',
        'cash_session_id',
        'expense_number',
        'expense_date',
        'delivered_to',
        'concept',
        'expense_time',
        'login',
        'exchange_rate',
        'amount',
        'shift',
        'status',
        'transaction_type',
        'supplier_invoice_number',
        'invoice_date',
        'supplier_tax_id',
        'supplier_authorization',
        'supplier_control_code',
        'has_invoice',
        'invoice_exchange_rate',
        'tax_book',
        'tax_credit',
        'from_cash_drawer',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date:Y-m-d',
            'exchange_rate' => 'decimal:3',
            'amount' => 'decimal:3',
            'invoice_date' => 'date:Y-m-d',
            'invoice_exchange_rate' => 'decimal:2',
            'tax_credit' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
