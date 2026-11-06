<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría del menú del POS (legacy: menugrupo_mysql).
 */
class Category extends Model
{
    use BelongsToRestaurant;

    protected $table = 'categories';

    protected $fillable = [
        'group_code',
        'group_name',
        'category_group',
        'status',
        'color',
        'image',
        'order_number',
        'image_path',
        'image_url',
        'day_code',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function productsBranch(): HasMany
    {
        return $this->hasMany(BranchProduct::class);
    }
}
