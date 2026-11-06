<?php

namespace App\Admin\Reports;

use App\Models\Staff;

class SalesByWaiter extends Report
{
    public function title(): string
    {
        return 'Ventas por mesero';
    }

    public function description(): string
    {
        return 'Ventas asignadas a cada mesero/vendedor al facturar, con su comisión según el porcentaje configurado.';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-id-badge';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'shift'];
    }

    public function columns(): array
    {
        return [
            'waiter' => ['Mesero / vendedor', 'text'],
            'sales' => ['Nro. ventas', 'number'],
            'total' => ['Total vendido (Bs)', 'currency'],
            'average' => ['Ticket promedio', 'currency'],
            'percent' => ['Comisión %', 'number'],
            'commission' => ['Comisión (Bs)', 'currency'],
        ];
    }

    public function withoutTotal(): array
    {
        return ['average', 'percent'];
    }

    public function rows(Filters $f): iterable
    {
        $personal = Staff::all()->keyBy('id');

        return $this->sales($f)->where('status', 'ACTIVE')
            ->groupBy('seller_id')
            ->selectRaw('seller_id, count(*) as sales, sum(invoice_total) as total')
            ->orderByDesc('total')
            ->get()
            ->map(function ($v) use ($personal) {
                $p = $personal->get($v->seller_id);
                $commission_rate = (float) ($p?->commission_rate ?? 0);

                return [
                    'waiter' => $p?->staff_name ?? 'Sin mesero asignado',
                    'sales' => (int) $v->sales,
                    'total' => (float) $v->total,
                    'average' => $v->total / max(1, $v->sales),
                    'percent' => $commission_rate,
                    'commission' => $v->total * $commission_rate / 100,
                ];
            });
    }
}
