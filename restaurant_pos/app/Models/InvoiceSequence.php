<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Numeración de facturas y órdenes por sucursal.
 */
class InvoiceSequence extends Model
{
    use BelongsToRestaurant;

    protected $table = 'invoice_sequences';

    protected $fillable = [
        'table_name',
        'branch_id',
        'folio',
        'real_folio',
        'daily_order',
        'print_flag',
        'receipt_counter',
        'receipt_multiplier',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function forSales(int $branchId): ?self
    {
        return static::where('table_name', config('pos.sales_sequence'))
            ->where('branch_id', $branchId)
            ->first();
    }
}
