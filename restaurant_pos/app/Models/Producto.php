<?php

namespace App\Models;

use App\Models\Concerns\PerteneceAEmpresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto vendible (legacy: productos_mysql).
 */
class Producto extends Model
{
    use PerteneceAEmpresa;

    protected $table = 'productos';

    protected $fillable = [
        'cod_prod',
        'nom_prod',
        'cantidad',
        'pre_venta',
        'unid',
        'categoria_id',
        'disponible',
        'estado',
        'presa',
        'familia',
        'unidad',
        'pre_costo',
        'colores',
        'nuevo',
        'stock',
        'cantidadac',
        'factor',
        'agrupa1',
        'agrupa2',
        'agrupa3',
        'cod_grupo',
        'cod_ins',
        'posicion',
        'img',
        'colorletra',
        'barcode',
        'ordenes',
        'ordenes1',
        'deconsumo',
        'invisible',
        'codeqr',
        'f_vence',
        'grupocmds',
        'grupocmds1',
        'indice',
        'dia',
        'tiempo',
        'tiempo_ini',
        'tiempo_fin',
        'impcoc',
        'imppunt1',
        'imppunt2',
        'imppunt3',
        'imppunt4',
        'imgnormal',
        'lun',
        'mar',
        'mier',
        'jue',
        'vie',
        'sab',
        'dom',
        'hora_inicio',
        'hora_fin',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'pre_venta' => 'decimal:2',
            'unid' => 'decimal:3',
            'pre_costo' => 'decimal:3',
            'stock' => 'decimal:2',
            'cantidadac' => 'decimal:2',
            'factor' => 'decimal:2',
            'f_vence' => 'date:Y-m-d',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function sucursales(): HasMany
    {
        return $this->hasMany(ProductoSucursal::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(ProductoOpcion::class);
    }

    public function receta(): HasMany
    {
        return $this->hasMany(InsumoProducto::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(GrupoProducto::class);
    }

    /** Campos que se toman de la configuración de la sucursal y no del producto. */
    public const CAMPOS_SUCURSAL = [
        'f_vence', 'impcoc', 'imppunt1', 'imppunt2', 'imppunt3', 'imppunt4',
        'lun', 'mar', 'mier', 'jue', 'vie', 'sab', 'dom', 'hora_inicio', 'hora_fin',
    ];

    /**
     * Productos habilitados en una sucursal, con precio, disponibilidad, horarios e impresoras
     * definidos en producto_sucursal (legacy: PRODUCTOS_MYSQL::mostrar / buscarXID).
     */
    public function scopeParaPos(Builder $query, int $sucursalId): Builder
    {
        $columnas = collect($this->getFillable())
            ->reject(fn ($c) => in_array($c, [...self::CAMPOS_SUCURSAL, 'pre_venta'], true))
            ->map(fn ($c) => "productos.$c")
            ->merge(array_map(fn ($c) => "ps.$c", self::CAMPOS_SUCURSAL))
            ->prepend('productos.id')
            ->push('productos.categoria_id as grupo', 'ps.categoria_id as categoria_sucursal_id', 'ps.id as producto_sucursal_id')
            ->all();

        return $query->join('producto_sucursal as ps', 'ps.producto_id', '=', 'productos.id')
            ->where('ps.sucursal_id', $sucursalId)
            ->select($columnas)
            // El precio configurado para la sucursal tiene prioridad sobre el precio general
            ->selectRaw('coalesce(nullif(ps.precio_venta, 0), productos.pre_venta) as pre_venta');
    }
}
