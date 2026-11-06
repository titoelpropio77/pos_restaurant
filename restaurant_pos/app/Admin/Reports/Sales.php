<?php

namespace App\Admin\Reports;

class Sales extends Report
{
    public function title(): string
    {
        return 'Listado de ventas';
    }

    public function description(): string
    {
        return 'Todas las ventas con su número de factura o recibo, cliente, cajero y forma de pago.';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-file-text-o';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'cajero', 'payment_method', 'shift', 'status'];
    }

    public function columns(): array
    {
        return [
            'date' => ['Fecha', 'date'],
            'time' => ['Hora', 'text'],
            'order_number' => ['Orden', 'text'],
            'document' => ['Factura / recibo', 'text'],
            'customer' => ['Cliente', 'text'],
            'tax_id' => ['NIT', 'text'],
            'cajero' => ['Cajero', 'text'],
            'shift' => ['Turno', 'text'],
            'payment_method' => ['Forma de pago', 'text'],
            'status' => ['Estado', 'text'],
            'total' => ['Total (Bs)', 'currency'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        return $this->sales($f)->orderBy('sale_date')->orderBy('id')->get()->map(fn ($v) => [
            '_venta' => $v->id,
            'date' => $v->sale_date,
            'time' => $v->sale_time,
            'order_number' => '#'.$v->order_number,
            'document' => $v->invoice_number > 0 && ($v->invoice_number === $v->real_invoice_number || ! $v->real_invoice_number) ? 'F-'.$v->invoice_number : 'R-'.$v->real_invoice_number,
            'customer' => $v->customer_name,
            'tax_id' => $v->customer_tax_id,
            'cajero' => $v->login,
            'shift' => $v->shift,
            'payment_method' => $v->payment_method,
            'status' => $v->status === 'ACTIVE' ? 'ACTIVA' : 'ANULADA',
            // Las anuladas se listan pero no suman
            'total' => $v->status === 'ACTIVE' ? (float) $v->invoice_total : 0.0,
        ]);
    }
}
