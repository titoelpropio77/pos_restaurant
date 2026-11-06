<?php

namespace App\Admin\Reports;

use App\Models\TableOrder;

class VoidedSales extends Report
{
    public function title(): string
    {
        return 'Ítems eliminados y mesas anuladas';
    }

    public function description(): string
    {
        return 'Control de lo que se quitó de las mesas antes de cobrar: ítems eliminados y mesas anuladas completas.';
    }

    public function category_group(): string
    {
        return 'Caja';
    }

    public function icon(): string
    {
        return 'fa-ban';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'type'];
    }

    public function types(): array
    {
        return ['ITEM' => 'Ítems eliminados', 'MESA' => 'Mesas anuladas'];
    }

    public function columns(): array
    {
        return [
            'date' => ['Fecha', 'date'],
            'time' => ['Hora', 'text'],
            'type' => ['Tipo', 'text'],
            'diningTable' => ['Mesa', 'text'],
            'product' => ['Producto', 'text'],
            'quantity' => ['Cantidad', 'number'],
            'total' => ['Importe (Bs)', 'currency'],
            'waiter' => ['Mesero', 'text'],
            'cajero' => ['Usuario', 'text'],
        ];
    }

    public function rows(Filters $f): iterable
    {
        return TableOrder::query()
            ->with('diningTable')
            ->whereBetween('sale_date', $f->range())
            ->when($f->branchId, fn ($q) => $q->where('branch_id', $f->branchId))
            ->where(fn ($q) => $q
                ->when($f->type !== 'MESA', fn ($q) => $q->orWhereRaw("coalesce(item_deleted, '') <> ''"))
                ->when($f->type !== 'ITEM', fn ($q) => $q->orWhere('voided', 'S')))
            ->orderBy('sale_date')->orderBy('id')
            ->get()
            ->map(fn ($p) => [
                'date' => $p->sale_date,
                'time' => $p->sale_time,
                'type' => $p->voided === 'S' ? 'Mesa anulada' : 'Ítem eliminado',
                'diningTable' => $p->diningTable?->table_number,
                'product' => $p->product_name,
                'quantity' => (float) $p->quantity,
                'total' => (float) $p->quantity * (float) $p->sale_price,
                'waiter' => $p->staff_name,
                'cajero' => $p->login,
            ]);
    }
}
