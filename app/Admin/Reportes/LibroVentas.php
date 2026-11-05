<?php

namespace App\Admin\Reportes;

class LibroVentas extends Reporte
{
    public function titulo(): string
    {
        return 'Libro de ventas IVA';
    }

    public function descripcion(): string
    {
        return 'Facturas emitidas en el formato del Libro de Ventas del SIN (débito fiscal 13%). Las anuladas figuran con estado A e importe cero.';
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
            'factura' => ['Nro. factura', 'texto'],
            'autorizacion' => ['Nro. autorización', 'texto'],
            'estado' => ['Estado', 'texto'],
            'nit' => ['NIT / CI cliente', 'texto'],
            'cliente' => ['Nombre o razón social', 'texto'],
            'total' => ['Importe total', 'moneda'],
            'ice' => ['ICE / IEHD / tasas', 'moneda'],
            'exentas' => ['Exportaciones y exentas', 'moneda'],
            'tasa_cero' => ['Ventas tasa cero', 'moneda'],
            'subtotal' => ['Subtotal', 'moneda'],
            'descuentos' => ['Descuentos', 'moneda'],
            'base' => ['Base débito fiscal', 'moneda'],
            'debito' => ['Débito fiscal', 'moneda'],
            'codigo' => ['Código de control', 'texto'],
        ];
    }

    public function filas(Filtros $f): iterable
    {
        $nro = 0;

        // Es factura cuando consumió número fiscal: folio > 0 y coincide con el número real (o no se usa recibo)
        return $this->ventas($f)
            ->where('fortax', '>', 0)
            ->where(fn ($q) => $q->whereColumn('fortax', 'tot_des')->orWhere('tot_des', 0))
            ->orderBy('f_venta')->orderBy('fortax')
            ->get()
            ->map(function ($v) use (&$nro) {
                $activa = $v->estado === 'ACTIVO';
                $total = $activa ? (float) $v->total_fact : 0.0;

                return [
                    '_venta' => $v->id,
                    'nro' => ++$nro,
                    'fecha' => $v->f_venta,
                    'factura' => $v->fortax,
                    'autorizacion' => $v->nroautori,
                    'estado' => $activa ? 'V' : 'A',
                    'nit' => $activa ? $v->nit_cli : '0',
                    'cliente' => $activa ? $v->nom_cli : 'ANULADA',
                    'total' => $total,
                    'ice' => 0.0,
                    'exentas' => 0.0,
                    'tasa_cero' => 0.0,
                    'subtotal' => $total,
                    'descuentos' => 0.0,
                    'base' => $total,
                    'debito' => round($total * 0.13, 2),
                    'codigo' => $activa ? $v->cifrado : '0',
                ];
            });
    }
}
