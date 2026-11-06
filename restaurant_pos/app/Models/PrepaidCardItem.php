<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo con tarjeta prepago (legacy: dettarjeta_mysql).
 */
class PrepaidCardItem extends Model
{
    use BelongsToRestaurant;

    protected $table = 'tarjeta_detalles';

    protected $fillable = [
        'sale_id',
        'branch_id',
        'order_number',
        'item',
        'product_code',
        'quantity',
        'cost_price',
        'sale_price',
        'exchange_rate',
        'sale_date',
        'sale_time',
        'product_name',
        'unit',
        'pending',
        'voided',
        'options_mode',
        'category_group',
        'closed',
        'shift',
        'family',
        'unit_factor',
        'service_type',
        'invoice_number',
        'real_invoice_number',
        'factor',
        'subtotal',
        'group_field',
        'qr_code',
        'group_code',
        'login',
        'orders_ref',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_price' => 'decimal:4',
            'sale_price' => 'decimal:2',
            'exchange_rate' => 'decimal:3',
            'sale_date' => 'date:Y-m-d',
            'unit_factor' => 'decimal:4',
            'factor' => 'decimal:5',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code');
    }
}
