<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Restaurante: cuenta del sistema dueña de sucursales, usuarios y demás datos de negocio.
 */
class Restaurant extends Model
{
    protected $table = 'restaurants';

    protected $fillable = [
        'name',
        'image',
        'web',
        'email',
        'activity',
        'status',
        'tax_id',
        'phone',
        'address',
        'options',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}
