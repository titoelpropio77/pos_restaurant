<?php

namespace App\Admin\Reports;

use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

class ReportGroups extends Report
{
    public function title(): string
    {
        return 'Grupos de reporte';
    }

    public function description(): string
    {
        return 'Unidades vendidas por grupo de reporte (p. ej. presas de pollo), según el factor de cada producto.';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-object-group';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'cajero', 'shift'];
    }

    public function columns(): array
    {
        return ['category_group' => ['Grupo', 'text'], 'code' => ['Código', 'text'], 'quantity' => ['Cantidad', 'number']];
    }

    public function rows(Filters $f): iterable
    {
        return DB::table('report_group_sales_view')
            ->where('restaurant_id', Tenant::id())
            ->whereBetween('sale_date', $f->range())
            ->when($f->branchId, fn ($q) => $q->where('branch_id', $f->branchId))
            ->when($f->cashier, fn ($q) => $q->where('login', $f->cashier))
            ->when($f->shift, fn ($q) => $q->where('shift', $f->shift))
            ->groupBy('group_name', 'group_code')
            ->selectRaw('group_name, group_code, sum(quantity) as quantity, min(order_number) as order_number')
            ->orderBy('order_number')
            ->get()
            ->map(fn ($g) => ['category_group' => $g->group_name, 'code' => $g->group_code, 'quantity' => (float) $g->quantity]);
    }
}
