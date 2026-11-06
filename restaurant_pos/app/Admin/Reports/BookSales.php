<?php

namespace App\Admin\Reports;

class BookSales extends Report
{
    public function title(): string
    {
        return 'Libro de ventas IVA';
    }

    public function description(): string
    {
        return 'Facturas emitidas en el formato del Libro de Ventas del SIN (débito fiscal 13%). Las anuladas figuran con estado A e importe cero.';
    }

    public function category_group(): string
    {
        return 'Impuestos';
    }

    public function icon(): string
    {
        return 'fa-book';
    }

    public function columns(): array
    {
        return [
            'number' => ['Nº', 'text'],
            'date' => ['Fecha factura', 'date'],
            'invoice_ref' => ['Nro. factura', 'text'],
            'authorization' => ['Nro. autorización', 'text'],
            'status' => ['Estado', 'text'],
            'tax_id' => ['NIT / CI cliente', 'text'],
            'customer' => ['Nombre o razón social', 'text'],
            'total' => ['Importe total', 'currency'],
            'ice' => ['ICE / IEHD / tasas', 'currency'],
            'exempt' => ['Exportaciones y exentas', 'currency'],
            'rate_zero' => ['Ventas tasa cero', 'currency'],
            'subtotal' => ['Subtotal', 'currency'],
            'discounts' => ['Descuentos', 'currency'],
            'base' => ['Base débito fiscal', 'currency'],
            'debit' => ['Débito fiscal', 'currency'],
            'code' => ['Código de control', 'text'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        $number = 0;

        // Es factura cuando consumió número fiscal: folio > 0 y coincide con el número real (o no se usa recibo)
        return $this->sales($f)
            ->where('invoice_number', '>', 0)
            ->where(fn ($q) => $q->whereColumn('invoice_number', 'real_invoice_number')->orWhere('real_invoice_number', 0))
            ->orderBy('sale_date')->orderBy('invoice_number')
            ->get()
            ->map(function ($v) use (&$number) {
                $active = $v->status === 'ACTIVE';
                $total = $active ? (float) $v->invoice_total : 0.0;

                return [
                    '_venta' => $v->id,
                    'number' => ++$number,
                    'date' => $v->sale_date,
                    'invoice_ref' => $v->invoice_number,
                    'authorization' => $v->authorization_number,
                    'status' => $active ? 'V' : 'A',
                    'tax_id' => $active ? $v->customer_tax_id : '0',
                    'customer' => $active ? $v->customer_name : 'ANULADA',
                    'total' => $total,
                    'ice' => 0.0,
                    'exempt' => 0.0,
                    'rate_zero' => 0.0,
                    'subtotal' => $total,
                    'discounts' => 0.0,
                    'base' => $total,
                    'debit' => round($total * 0.13, 2),
                    'code' => $active ? $v->control_code : '0',
                ];
            });
    }
}
