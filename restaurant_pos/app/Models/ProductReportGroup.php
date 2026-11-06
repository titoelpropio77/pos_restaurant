<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Relación producto - grupo de reporte.
 */
class ProductReportGroup extends Model
{
    use BelongsToRestaurant;

    protected $table = 'product_report_group';

    protected $fillable = [
        'report_group_id',
        'product_id',
        'quantity',
        'unit',
        'factor',
        'product_name',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit' => 'decimal:3',
            'factor' => 'decimal:2',
        ];
    }

    public function category_group(): BelongsTo
    {
        return $this->belongsTo(ReportGroup::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
