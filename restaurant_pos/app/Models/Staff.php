<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Personal operativo del POS: meseros, cocina... (legacy: empleados_mysql).
 */
class Staff extends Model
{
    use BelongsToRestaurant;

    protected $table = 'staff';

    protected $fillable = [
        'staff_code',
        'staff_name',
        'staff_address',
        'staff_id_number',
        'position',
        'salary',
        'status',
        'commission_rate',
        'staff_color',
        'code',
        'branch_id',
    ];

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
