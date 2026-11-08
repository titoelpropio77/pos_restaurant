<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingAuthorization;
use App\Models\Branch;
use App\Models\DiningTable;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supply;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Inicio del panel: indicadores de ventas, gráfico, productos más vendidos y alertas.
 */
class DashboardController extends Controller
{
    public const PERIODS = ['today' => 'Hoy', '7' => 'Últimos 7 días', '30' => 'Últimos 30 días', 'month' => 'Este mes'];

    public function __invoke(Request $request): View
    {
        $period = array_key_exists($request->query('period'), self::PERIODS) ? $request->query('period') : '7';
        $branchId = $request->integer('branch') ?: null;

        [$from, $to] = match ($period) {
            'today' => [today(), today()],
            'month' => [today()->startOfMonth(), today()],
            default => [today()->subDays((int) $period - 1), today()],
        };

        $sales = fn () => Sale::whereBetween('sale_date', [$from->toDateString(), $to->toDateString()])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));
        $active = fn () => $sales()->where('status', 'ACTIVE');

        $total = (float) $active()->sum('invoice_total');
        $quantity = $active()->count();

        $indicators = [
            ['Ventas', 'Bs '.number_format($total, 2), 'fa-money', 'Ventas activas del período'],
            ['Nro. de ventas', number_format($quantity), 'fa-file-text-o', 'Tickets emitidos'],
            ['Ticket promedio', 'Bs '.number_format($quantity ? $total / $quantity : 0, 2), 'fa-calculator', 'Venta promedio'],
            ['Anulado', 'Bs '.number_format((float) $sales()->where('status', 'INACTIVE')->sum('invoice_total'), 2), 'fa-ban', 'Ventas anuladas'],
            ['Gastos de caja', 'Bs '.number_format((float) Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('status', 'ACTIVE')->where('transaction_type', 0)->sum('amount'), 2), 'fa-arrow-circle-down', 'Egresos registrados'],
        ];

        return view('admin.dashboard', [
            'period' => $period,
            'branchId' => $branchId,
            'branches' => Branch::orderBy('name')->pluck('name', 'id'),
            'indicators' => $indicators,
            'series' => $period === 'today' ? $this->porTime($active()) : $this->porDay($active(), $from, $to),
            'titleSeries' => $period === 'today' ? 'Ventas de hoy por hora (Bs)' : 'Ventas por día (Bs)',
            'topProducts' => SaleItem::query()
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->whereIn('sales.id', $active()->select('id'))
                ->groupBy('sale_items.product_name')
                ->selectRaw('sale_items.product_name, sum(sale_items.quantity) as quantity, sum(cast(nullif(sale_items.subtotal, \'\') as numeric)) as importe')
                ->orderByDesc('quantity')
                ->limit(8)
                ->get(),
            'methodsPayment' => $active()->groupBy('payment_method')
                ->selectRaw('payment_method, count(*) as sales, sum(invoice_total) as total')
                ->orderByDesc('total')->get(),
            'lastSales' => $sales()->with('branch')->latest('id')->limit(8)->get(),
            'stockLow' => Supply::where('status', 'ACTIVE')
                ->withSum('stocks as stock_total', 'current_stock')
                ->get()
                ->filter(fn ($i) => (float) $i->stock_total <= (float) $i->min_stock && (float) $i->min_stock > 0)
                ->take(8),
            'authorizations' => ! Tenant::option('billing') ? collect() : Branch::where('status', 'ACTIVE')->get()
                ->map(fn ($s) => [$s, $a = BillingAuthorization::currentFor($s->id), $a?->daysUntilExpiry(today())])
                // Una dosificación desactivada no avisa: la sucursal vende con nota de venta
                ->reject(fn ($d) => $d[1] && ! $d[1]->isActive())
                ->map(fn ($d) => [$d[0], $d[2]])
                ->filter(fn ($d) => $d[1] === null || $d[1] <= config('pos.days_warning_authorization')),
            'tablesOccupied' => DiningTable::where('occupied', 'S')->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count(),
        ]);
    }

    /** @return array<int, array{etiqueta: string, detalle: string, valor: float}> */
    private function porDay($query, Carbon $from, Carbon $to): array
    {
        $totals = $query->groupBy('sale_date')->selectRaw('sale_date, sum(invoice_total) as total')->pluck('total', 'sale_date');
        $series = [];

        for ($day_code = $from->copy(); $day_code->lte($to); $day_code->addDay()) {
            $series[] = [
                'label' => $day_code->format('d/m'),
                'detail' => $day_code->locale('es')->isoFormat('dddd D [de] MMMM'),
                'value' => (float) ($totals[$day_code->toDateString()] ?? 0),
            ];
        }

        return $series;
    }

    private function porTime($query): array
    {
        $totals = $query->groupBy(DB::raw("split_part(sale_time, ':', 1)"))
            ->selectRaw("cast(split_part(sale_time, ':', 1) as int) as time, sum(invoice_total) as total")
            ->pluck('total', 'time');

        $hours = $totals->keys()->whenEmpty(fn ($c) => $c->push(12));
        $series = [];

        for ($h = min(8, $hours->min()); $h <= max(22, $hours->max()); $h++) {
            $series[] = ['label' => sprintf('%02d', $h), 'detail' => sprintf('%02d:00 a %02d:59', $h, $h), 'value' => (float) ($totals[$h] ?? 0)];
        }

        return $series;
    }
}
