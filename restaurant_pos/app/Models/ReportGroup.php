<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de reporte (legacy: grupos_mysql).
 */
class ReportGroup extends Model
{
    use BelongsToRestaurant;

    protected $table = 'report_groups';

    protected $fillable = [
        'group_code',
        'group_name',
        'factor',
        'order_number',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(ProductReportGroup::class);
    }
}
