<?php

namespace App\Admin\Reportes;

class MejoresClientes extends Reporte
{
    public function titulo(): string
    {
        return 'Clientes';
    }

    public function descripcion(): string
    {
        return 'Clientes con más compras en el período (excluye «SIN NOMBRE»).';
    }

    public function grupo(): string
    {
        return 'Ventas';
    }

    public function icono(): string
    {
        return 'fa-address-book-o';
    }

    public function columnas(): array
    {
        return [
            'cliente' => ['Cliente', 'texto'],
            'nit' => ['NIT / CI', 'texto'],
            'compras' => ['Compras', 'numero'],
            'total' => ['Total (Bs)', 'moneda'],
            'ultima' => ['Última compra', 'fecha'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        return $this->ventas($f)->where('estado', 'ACTIVO')
            ->where('nom_cli', '<>', 'SIN NOMBRE')
            ->groupBy('nom_cli', 'nit_cli')
            ->selectRaw('nom_cli, nit_cli, count(*) as compras, sum(total_fact) as total, max(f_venta) as ultima')
            ->orderByDesc('total')
            ->limit(500)
            ->get()
            ->map(fn ($c) => ['cliente' => $c->nom_cli, 'nit' => $c->nit_cli, 'compras' => (int) $c->compras, 'total' => (float) $c->total, 'ultima' => $c->ultima]);
    }
}
