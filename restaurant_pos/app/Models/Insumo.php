<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Insumos / materia prima.
 */
class Insumo extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'insumos';

    protected $fillable = [
        'cod_ins',
        'nom_insumo',
        'stock_min',
        'stock_act',
        'medida',
        'medidam',
        'ope_mm',
        'val_for_mm',
        'ope_mx',
        'medidax',
        'val_for_mx',
        'adicionado',
        'can_adi',
        'estado',
        'cod_prod',
        'unidad',
        'pre_costo',
        'ppm',
        'precio_ultima_compra',
    ];

    protected function casts(): array
    {
        return [
            'stock_min' => 'decimal:3',
            'stock_act' => 'decimal:3',
            'val_for_mm' => 'decimal:3',
            'val_for_mx' => 'decimal:3',
            'can_adi' => 'decimal:3',
            'unidad' => 'decimal:3',
            'pre_costo' => 'decimal:4',
            'ppm' => 'decimal:4',
            'precio_ultima_compra' => 'decimal:4',
        ];
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(InsumoAlmacen::class);
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(KardexInsumo::class);
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(InsumoProducto::class);
    }
}
