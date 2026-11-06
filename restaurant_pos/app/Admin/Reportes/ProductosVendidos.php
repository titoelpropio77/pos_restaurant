<?php

namespace App\Admin\Reportes;

use App\Models\VentaDetalle;

class ProductosVendidos extends Reporte
{
    public function titulo(): string
    {
        return 'Productos vendidos';
    }

    public function descripcion(): string
    {
        return 'Ranking de productos por cantidad e importe vendido (sin ventas anuladas).';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-trophy';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'categoria', 'cajero', 'turno'];
    }

    public function columnas(): array
    {
        return [
            'producto' => ['Producto', 'texto'],
            'categoria' => ['Categoría', 'texto'],
            'cantidad' => ['Cantidad', 'numero'],
            'precio' => ['Precio prom.', 'moneda'],
            'importe' => ['Importe (Bs)', 'moneda'],
            'participacion' => ['% del total', 'porcentaje'],
        ];
    }

    public function sinTotal(): array
    {
        return ['precio'];
    }

    public function filas(Filtros $f): iterable
    {
        $filas = VentaDetalle::query()
            ->joinSub($this->ventas($f)->where('estado', 'ACTIVO')->select('id'), 'v', 'v.id', '=', 'venta_detalles.venta_id')
            ->leftJoin('productos', 'productos.id', '=', 'venta_detalles.cod_prod')
            ->leftJoin('categorias', 'categorias.id', '=', 'productos.categoria_id')
            ->when($f->categoriaId, fn ($q) => $q->where('productos.categoria_id', $f->categoriaId))
            ->groupBy('venta_detalles.nom_prod', 'categorias.nom_grupo')
            ->selectRaw('venta_detalles.nom_prod as producto, categorias.nom_grupo as categoria, sum(venta_detalles.cantidad) as cantidad')
            ->selectRaw("sum(coalesce(cast(nullif(venta_detalles.agrupa1, '') as numeric), venta_detalles.cantidad * venta_detalles.pre_venta)) as importe")
            ->orderByDesc('importe')
            ->get();

        $total = max(0.01, (float) $filas->sum('importe'));

        return $filas->map(fn ($p) => [
            'producto' => $p->producto,
            'categoria' => $p->categoria ?? '—',
            'cantidad' => (float) $p->cantidad,
            'precio' => $p->cantidad > 0 ? $p->importe / $p->cantidad : 0.0,
            'importe' => (float) $p->importe,
            'participacion' => $p->importe / $total * 100,
        ]);
    }
}
