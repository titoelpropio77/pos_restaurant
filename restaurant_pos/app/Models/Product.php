<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto vendible (legacy: productos_mysql).
 */
class Product extends Model
{
    use BelongsToRestaurant;

    protected $table = 'products';

    protected $fillable = [
        'product_code',
        'product_name',
        'description',
        'quantity',
        'sale_price',
        'unit_factor',
        'category_id',
        'available',
        'status',
        'options_mode',
        'family',
        'unit',
        'cost_price',
        'color',
        'is_new',
        'stock',
        'factor',
        'subtotal',
        'group_field',
        'qr_code',
        'group_code',
        'supply_code',
        'image_url',
        'barcode',
        'orders_ref',
        'is_consumption',
        'expires_on',
        'index_number',
        'day_code',
        'timed',
        'time_start',
        'time_end',
        'print_kitchen',
        'print_station_1',
        'print_station_2',
        'print_station_3',
        'print_station_4',
        'image_path',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
        'sat',
        'sun',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'unit_factor' => 'decimal:3',
            'cost_price' => 'decimal:3',
            'stock' => 'decimal:2',
            'factor' => 'decimal:2',
            'expires_on' => 'date:Y-m-d',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function recipe(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ProductReportGroup::class);
    }

    /** Campos que se toman de la configuración de la sucursal y no del producto. */
    public const BRANCH_FIELDS = [
        'expires_on', 'print_kitchen', 'print_station_1', 'print_station_2', 'print_station_3', 'print_station_4',
        'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun', 'start_time', 'end_time',
    ];

    /**
     * Productos habilitados en una sucursal, con precio, disponibilidad, horarios e impresoras
     * definidos en producto_sucursal (legacy: PRODUCTOS_MYSQL::mostrar / buscarXID).
     */
    public function scopeForPos(Builder $query, int $branchId): Builder
    {
        $columns = collect($this->getFillable())
            ->reject(fn ($c) => in_array($c, [...self::BRANCH_FIELDS, 'sale_price'], true))
            ->map(fn ($c) => "products.$c")
            ->merge(array_map(fn ($c) => "ps.$c", self::BRANCH_FIELDS))
            ->prepend('products.id')
            ->push('products.category_id as category_group', 'ps.category_id as branch_category_id', 'ps.id as branch_product_id')
            ->all();

        return $query->join('branch_products as ps', 'ps.product_id', '=', 'products.id')
            ->where('ps.branch_id', $branchId)
            ->select($columns)
            // El precio configurado para la sucursal tiene prioridad sobre el precio general
            ->selectRaw('coalesce(nullif(ps.sale_price, 0), products.sale_price) as sale_price');
    }
}
