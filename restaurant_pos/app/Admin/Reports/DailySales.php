<?php

namespace App\Admin\Reports;

class DailySales extends Report
{
    public function title(): string
    {
        return 'Ventas por día';
    }

    public function description(): string
    {
        return 'Resumen diario: cantidad de ventas, montos por forma de pago, anulaciones y ticket promedio.';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-calendar';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'cajero', 'shift'];
    }

    public function columns(): array
    {
        return [
            'date' => ['Fecha', 'date'],
            'sales' => ['Nro. ventas', 'number'],
            'cash' => ['Contado', 'currency'],
            'credit' => ['Crédito', 'currency'],
            'consumption' => ['Consumo interno', 'currency'],
            'qr' => ['QR', 'currency'],
            'others' => ['Otros', 'currency'],
            'voided' => ['Anulado', 'currency'],
            'total' => ['Total neto', 'currency'],
            'average' => ['Ticket promedio', 'currency'],
        ];
    }

    public function withoutTotal(): array
    {
        return ['average'];
    }

    public function rows(Filters $f): iterable
    {
        $active = "status = 'ACTIVE'";

        return $this->sales($f)->groupBy('sale_date')->orderBy('sale_date')
            ->select('sale_date')
            ->selectRaw("count(*) filter (where $active) as sales")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active and payment_method = 'CONTADO'), 0) as cash")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active and payment_method = 'CREDITO'), 0) as credit")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active and payment_method = 'CONSUMO'), 0) as consumption")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active and payment_method = 'QR'), 0) as qr")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active and payment_method not in ('CONTADO', 'CREDITO', 'CONSUMO', 'QR')), 0) as others")
            ->selectRaw("coalesce(sum(invoice_total) filter (where status <> 'ACTIVE'), 0) as voided")
            ->selectRaw("coalesce(sum(invoice_total) filter (where $active), 0) as total")
            ->get()
            ->map(fn ($d) => [
                'date' => $d->sale_date,
                'sales' => (int) $d->sales,
                'cash' => (float) $d->cash,
                'credit' => (float) $d->credit,
                'consumption' => (float) $d->consumption,
                'qr' => (float) $d->qr,
                'others' => (float) $d->others,
                'voided' => (float) $d->voided,
                'total' => (float) $d->total,
                'average' => $d->sales ? $d->total / $d->sales : 0.0,
            ]);
    }

    public function totals(array $rows): array
    {
        $totals = parent::totals($rows);
        $totals['average'] = $totals['sales'] ? $totals['total'] / $totals['sales'] : 0;

        return $totals;
    }
}
