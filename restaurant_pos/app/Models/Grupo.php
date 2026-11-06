<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grupo de reporte (legacy: grupos_mysql).
 */
class Grupo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'grupos';

    protected $fillable = [
        'cod_grupo',
        'nom_grupo',
        'factor',
        'orden',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(GrupoProducto::class);
    }
}
