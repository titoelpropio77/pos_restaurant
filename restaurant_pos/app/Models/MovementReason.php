<?php

namespace App\Models;

use App\Models\Concerns\BelongsToRestaurant;
use Illuminate\Database\Eloquent\Model;

/**
 * Motivos de movimiento de inventario.
 */
class MovementReason extends Model
{
    use BelongsToRestaurant;

    protected $table = 'movement_reasons';

    protected $fillable = [
        'name',
        'status',
    ];
}
