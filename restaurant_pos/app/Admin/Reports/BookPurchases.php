<?php

namespace App\Admin\Reports;

use App\Models\Expense;

class BookPurchases extends Report
{
    public function title(): string
    {
        return 'Libro de compras IVA';
    }

    public function description(): string
    {
        return 'Compras con factura registradas como egreso con crédito fiscal, en el formato del Libro de Compras del SIN.';
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
            'tax_id' => ['NIT proveedor', 'text'],
            'supplier' => ['Nombre o razón social', 'text'],
            'invoice_ref' => ['Nro. factura', 'text'],
            'authorization' => ['Nro. autorización', 'text'],
            'total' => ['Importe total', 'currency'],
            'no_subject' => ['Importe no sujeto a CF', 'currency'],
            'subtotal' => ['Subtotal', 'currency'],
            'discounts' => ['Descuentos', 'currency'],
            'base' => ['Base crédito fiscal', 'currency'],
            'credit' => ['Crédito fiscal', 'currency'],
            'code' => ['Código de control', 'text'],
            'type' => ['Tipo de compra', 'text'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        $number = 0;

        return Expense::query()
            ->where('tax_book', 1)
            ->where('status', 'ACTIVE')
            ->whereBetween('invoice_date', $f->range())
            ->when($f->branchId, fn ($q) => $q->where('branch_id', $f->branchId))
            ->orderBy('invoice_date')->orderBy('id')
            ->get()
            ->map(function ($g) use (&$number) {
                $total = (float) $g->amount;

                return [
                    'number' => ++$number,
                    'date' => $g->invoice_date,
                    'tax_id' => $g->supplier_tax_id,
                    'supplier' => $g->delivered_to,
                    'invoice_ref' => $g->supplier_invoice_number,
                    'authorization' => $g->supplier_authorization,
                    'total' => $total,
                    'no_subject' => 0.0,
                    'subtotal' => $total,
                    'discounts' => 0.0,
                    'base' => $total,
                    'credit' => (float) ($g->tax_credit ?: round($total * 0.13, 2)),
                    'code' => $g->supplier_control_code ?: '0',
                    'type' => '1',
                ];
            });
    }
}
