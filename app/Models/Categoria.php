<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Categoría del menú del POS (legacy: menugrupo_mysql).
 */
class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'cod_grupo',
        'nom_grupo',
        'grupo',
        'estado',
        'colores',
        'colorletra',
        'imagen',
        'orden',
        'imgnormal',
        'imgpresion',
        'imgsobre',
        'imgnodispo',
        'img',
        'invisible',
        'dia',
    ];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function productosSucursal(): HasMany
    {
        return $this->hasMany(ProductoSucursal::class);
    }
}
