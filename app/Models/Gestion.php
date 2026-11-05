<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Gestión contable.
 */
class Gestion extends Model
{
    use SoftDeletes;

    protected $table = 'gestiones';

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'nombre_gestion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date:Y-m-d',
            'fecha_fin' => 'date:Y-m-d',
        ];
    }
}
