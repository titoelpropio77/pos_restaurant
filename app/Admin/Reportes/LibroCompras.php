<?php

namespace App\Admin\Reportes;

use App\Models\Gasto;

class LibroCompras extends Reporte
{
    public function titulo(): string
    {
        return 'Libro de compras IVA';
    }

    public function descripcion(): string
    {
        return 'Compras con factura registradas como egreso con crédito fiscal, en el formato del Libro de Compras del SIN.';
    }

    public function grupo(): string
    {
        return 'Impuestos';
    }

    public function icono(): string
    {
        return 'fa-book';
    }

    public function columnas(): array
    {
        return [
            'nro' => ['Nº', 'texto'],
            'fecha' => ['Fecha factura', 'fecha'],
            'nit' => ['NIT proveedor', 'texto'],
            'proveedor' => ['Nombre o razón social', 'texto'],
            'factura' => ['Nro. factura', 'texto'],
            'autorizacion' => ['Nro. autorización', 'texto'],
            'total' => ['Importe total', 'moneda'],
            'no_sujeto' => ['Importe no sujeto a CF', 'moneda'],
            'subtotal' => ['Subtotal', 'moneda'],
            'descuentos' => ['Descuentos', 'moneda'],
            'base' => ['Base crédito fiscal', 'moneda'],
            'credito' => ['Crédito fiscal', 'moneda'],
            'codigo' => ['Código de control', 'texto'],
            'tipo' => ['Tipo de compra', 'texto'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        $nro = 0;

        return Gasto::query()
            ->where('impuesto', 1)
            ->where('estado', 'ACTIVO')
            ->whereBetween('fecha_factura', $f->rango())
            ->when($f->sucursalId, fn ($q) => $q->where('sucursal_id', $f->sucursalId))
            ->orderBy('fecha_factura')->orderBy('id')
            ->get()
            ->map(function ($g) use (&$nro) {
                $total = (float) $g->monto;

                return [
                    'nro' => ++$nro,
                    'fecha' => $g->fecha_factura,
                    'nit' => $g->nit_proveedor,
                    'proveedor' => $g->entregadoa,
                    'factura' => $g->nrofactura,
                    'autorizacion' => $g->nro_autorizacion,
                    'total' => $total,
                    'no_sujeto' => 0.0,
                    'subtotal' => $total,
                    'descuentos' => 0.0,
                    'base' => $total,
                    'credito' => (float) ($g->credito_fiscal ?: round($total * 0.13, 2)),
                    'codigo' => $g->codigo_control ?: '0',
                    'tipo' => '1',
                ];
            });
    }
}
