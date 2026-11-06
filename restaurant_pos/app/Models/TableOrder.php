<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ítem pedido en una mesa abierta (legacy: productos_mesas_mysql).
 */
class TableOrder extends Model
{
    use BelongsToRestaurant;

    protected $table = 'table_orders';

    protected $fillable = [
        'dining_table_id',
        'branch_id',
        'invoice_ref',
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
        'note',
        'item_deleted',
        'staff_name',
        'deleted_flag',
        'timed',
        'time_start',
        'time_end',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'cost_price' => 'decimal:4',
            'sale_price' => 'decimal:2',
            'exchange_rate' => 'decimal:2',
            'sale_date' => 'date:Y-m-d',
            'unit_factor' => 'decimal:3',
            'factor' => 'decimal:5',
        ];
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_code');
    }

    /**
     * Ítems todavía abiertos en la mesa: no cobrados, no traspasados ni eliminados.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereRaw("coalesce(deleted_flag, '') = ''")
            ->whereRaw("coalesce(item_deleted, '') = ''")
            ->whereRaw("coalesce(closed, '') = ''");
    }
}
