<?php

namespace App\Admin\Reports;

use App\Models\SaleItem;

class ProductsSold extends Report
{
    public function title(): string
    {
        return 'Productos vendidos';
    }

    public function description(): string
    {
        return 'Ranking de productos por cantidad e importe vendido (sin ventas anuladas).';
    }

    public function category_group(): string
    {
        return 'Ventas';
    }

    public function icon(): string
    {
        return 'fa-trophy';
    }

    public function filters(): array
    {
        return ['dates', 'branch', 'category', 'cajero', 'shift'];
    }

    public function columns(): array
    {
        return [
            'product' => ['Producto', 'text'],
            'category' => ['Categoría', 'text'],
            'quantity' => ['Cantidad', 'number'],
            'price' => ['Precio prom.', 'currency'],
            'total' => ['Importe (Bs)', 'currency'],
            'share' => ['% del total', 'percent'],
        ];
    }

    public function withoutTotal(): array
    {
        return ['price'];
    }

    public function rows(Filters $f): iterable
    {
        $rows = SaleItem::query()
            ->joinSub($this->sales($f)->where('status', 'ACTIVE')->select('id'), 'v', 'v.id', '=', 'sale_items.sale_id')
            ->leftJoin('products', 'products.id', '=', 'sale_items.product_code')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->when($f->categoryId, fn ($q) => $q->where('products.category_id', $f->categoryId))
            ->groupBy('sale_items.product_name', 'categories.group_name')
            ->selectRaw('sale_items.product_name as producto, categories.group_name as categoria, sum(sale_items.quantity) as quantity')
            ->selectRaw("sum(coalesce(cast(nullif(sale_items.subtotal, '') as numeric), sale_items.quantity * sale_items.sale_price)) as total")
            ->orderByDesc('total')
            ->get();

        $total = max(0.01, (float) $rows->sum('total'));

        return $rows->map(fn ($p) => [
            'product' => $p->product,
            'category' => $p->category ?? '—',
            'quantity' => (float) $p->quantity,
            'price' => $p->quantity > 0 ? $p->total / $p->quantity : 0.0,
            'total' => (float) $p->total,
            'share' => $p->total / $total * 100,
        ]);
    }
}
