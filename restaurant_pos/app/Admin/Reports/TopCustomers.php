<?php

namespace App\Admin\Reports;

class TopCustomers extends Report
{
    public function title(): string
    {
        return 'Clientes';
    }

    public function description(): string
    {
        return 'Clientes con más compras en el período (excluye «SIN NOMBRE»).';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-address-book-o';
    }

    public function columns(): array
    {
        return [
            'customer' => ['Cliente', 'text'],
            'tax_id' => ['NIT / CI', 'text'],
            'purchases' => ['Compras', 'number'],
            'total' => ['Total (Bs)', 'currency'],
            'last' => ['Última compra', 'date'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        return $this->sales($f)->where('status', 'ACTIVE')
            ->where('customer_name', '<>', 'SIN NOMBRE')
            ->groupBy('customer_name', 'customer_tax_id')
            ->selectRaw('customer_name, customer_tax_id, count(*) as compras, sum(invoice_total) as total, max(sale_date) as ultima')
            ->orderByDesc('total')
            ->limit(500)
            ->get()
            ->map(fn ($c) => ['customer' => $c->customer_name, 'tax_id' => $c->customer_tax_id, 'purchases' => (int) $c->purchases, 'total' => (float) $c->total, 'last' => $c->last]);
    }
}
