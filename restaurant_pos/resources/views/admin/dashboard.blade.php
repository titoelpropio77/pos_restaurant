@extends('admin.layout')

@section('title', 'Inicio')

@section('content')
    {{-- Filtros: afectan a todo lo que está debajo --}}
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex max-w-full overflow-x-auto rounded-lg bg-white p-1 whitespace-nowrap shadow-sm ring-1 ring-black/5">
            @foreach (\App\Http\Controllers\Admin\DashboardController::PERIODS as $key => $text)
                <a href="{{ route('admin.dashboard', ['period' => $key, 'branch' => $branchId]) }}"
                   @class(['rounded-md px-3 py-1.5 text-sm font-semibold transition',
                           'bg-brand-500 text-panel' => $period === (string) $key,
                           'text-muted hover:text-ink' => $period !== (string) $key])>{{ $text }}</a>
            @endforeach
        </div>
        <input type="hidden" name="period" value="{{ $period }}">
        <select name="branch" class="field w-auto" onchange="this.form.submit()">
            <option value="">Todas las sucursales</option>
            @foreach ($branches as $id => $name)
                <option value="{{ $id }}" @selected($branchId == $id)>{{ $name }}</option>
            @endforeach
        </select>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($indicators as [$label, $value, $icon, $help])
            <div class="card p-4">
                <div class="flex items-center justify-between text-sm text-muted">
                    {{ $label }}
                    <i class="fa {{ $icon }} text-slate-400"></i>
                </div>
                <p @class(['mt-2 font-bold tabular-nums', 'text-3xl' => $loop->first, 'text-2xl' => ! $loop->first])>{{ $value }}</p>
                <p class="mt-1 text-xs text-muted">{{ $help }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-5 grid gap-5 xl:grid-cols-3">
        <x-admin.chart-columns :series="$series" :title="$titleSeries" class="xl:col-span-2" />

        <div class="card p-5">
            <h2 class="mb-3 font-bold">Alertas</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"><i class="fa fa-square-o"></i></span>
                    <span><strong>{{ $tablesOccupied }}</strong> mesa(s) ocupada(s) en este momento</span>
                </li>
                @foreach ($authorizations as [$branch, $days])
                    <li class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-700"><i class="fa fa-exclamation-triangle"></i></span>
                        <span>
                            <strong>{{ $branch->name }}:</strong>
                            @if ($days === null) sin dosificación registrada
                            @elseif ($days < 0) la dosificación venció hace {{ abs($days) }} día(s)
                            @else la dosificación vence en {{ $days }} día(s)
                            @endif
                            <a href="{{ route('admin.billing.edit', $branch) }}" class="font-semibold text-brand-600 hover:underline">Actualizar</a>
                        </span>
                    </li>
                @endforeach
                @forelse ($stockLow as $supply)
                    <li class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="fa fa-cubes"></i></span>
                        <span><strong>{{ $supply->supply_name }}</strong>: stock {{ rtrim(rtrim(number_format((float) $supply->stock_total, 2), '0'), '.') }} {{ $supply->measure }} (mínimo {{ (float) $supply->min_stock }})</span>
                    </li>
                @empty
                    <li class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-700"><i class="fa fa-check"></i></span>
                        <span>Ningún insumo por debajo del stock mínimo</span>
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="card overflow-hidden">
            <h2 class="px-5 pt-5 pb-3 font-bold">Productos más vendidos</h2>
            @php $maxQuantity = max(1, (float) $topProducts->max('quantity')); @endphp
            <table class="data-table">
                <thead><tr><th>Producto</th><th class="text-right">Cant.</th><th class="text-right">Bs</th></tr></thead>
                <tbody>
                    @forelse ($topProducts as $p)
                        <tr>
                            <td>
                                {{ $p->product_name }}
                                <div class="mt-1 h-1.5 rounded-full bg-brand-100"><div class="h-1.5 rounded-full bg-brand-500" style="width: {{ $p->quantity / $maxQuantity * 100 }}%"></div></div>
                            </td>
                            <td class="num">{{ (float) $p->quantity }}</td>
                            <td class="num">{{ number_format((float) $p->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-muted">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <h2 class="px-5 pt-5 pb-3 font-bold">Formas de pago</h2>
            @php $totalPayments = max(0.01, (float) $methodsPayment->sum('total')); @endphp
            <table class="data-table">
                <thead><tr><th>Forma de pago</th><th class="text-right">Ventas</th><th class="text-right">Bs</th><th class="text-right">%</th></tr></thead>
                <tbody>
                    @forelse ($methodsPayment as $f)
                        <tr>
                            <td class="font-semibold">{{ $f->payment_method }}</td>
                            <td class="num">{{ $f->sales }}</td>
                            <td class="num">{{ number_format((float) $f->total, 2) }}</td>
                            <td class="num">{{ number_format($f->total / $totalPayments * 100, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-muted">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Últimas ventas</h2>
                <a href="{{ route('admin.reports.show', 'sales') }}" class="text-sm font-semibold text-brand-600 hover:underline">Ver todas</a>
            </div>
            <table class="data-table">
                <thead><tr><th>Orden</th><th>Cliente</th><th class="text-right">Bs</th></tr></thead>
                <tbody>
                    @forelse ($lastSales as $v)
                        <tr class="cursor-pointer" onclick="location.href='{{ route('admin.sales.show', $v) }}'">
                            <td><span class="font-semibold">#{{ $v->order_number }}</span> <span class="text-xs text-muted">{{ $v->sale_time }}</span></td>
                            <td class="max-w-36 truncate">{{ $v->customer_name }}</td>
                            <td @class(['num', 'text-red-600 line-through' => $v->status !== 'ACTIVE'])>{{ number_format((float) $v->invoice_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-muted">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
