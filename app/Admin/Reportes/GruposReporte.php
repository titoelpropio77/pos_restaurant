<?php

namespace App\Admin\Reportes;

use Illuminate\Support\Facades\DB;

class GruposReporte extends Reporte
{
    public function titulo(): string
    {
        return 'Grupos de reporte';
    }

    public function descripcion(): string
    {
        return 'Unidades vendidas por grupo de reporte (p. ej. presas de pollo), según el factor de cada producto.';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-object-group';
    }

    public function filtros(): array
    {
        return ['fechas', 'sucursal', 'cajero', 'turno'];
    }

    public function columnas(): array
    {
        return ['grupo' => ['Grupo', 'texto'], 'codigo' => ['Código', 'texto'], 'cantidad' => ['Cantidad', 'numero']];
    }

    public function filas(Filtros $f): iterable
    {
        return DB::table('vista_grupo_producto_ventas')
            ->whereBetween('f_venta', $f->rango())
            ->when($f->sucursalId, fn ($q) => $q->where('sucursal_id', $f->sucursalId))
            ->when($f->cajero, fn ($q) => $q->where('login', $f->cajero))
            ->when($f->turno, fn ($q) => $q->where('turno', $f->turno))
            ->groupBy('nom_grupo', 'cod_grupo')
            ->selectRaw('nom_grupo, cod_grupo, sum(cantidad) as cantidad, min(orden) as orden')
            ->orderBy('orden')
            ->get()
            ->map(fn ($g) => ['grupo' => $g->nom_grupo, 'codigo' => $g->cod_grupo, 'cantidad' => (float) $g->cantidad]);
    }
}
