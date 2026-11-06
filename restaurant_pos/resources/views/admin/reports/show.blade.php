@extends('admin.layout')

@section('title', $report->title())

@php
    $used = $report->filters();
    $columns = $report->columns();
    $renderValue = function ($value, $format) {
        if ($value === null || $value === '') return '—';
        return match ($format) {
            'date' => ($value instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($value) : \Illuminate\Support\Carbon::parse($value))->format('d/m/Y'),
            'currency' => number_format((float) $value, 2),
            'number' => rtrim(rtrim(number_format((float) $value, 3), '0'), '.'),
            'percent' => number_format((float) $value, 1).'%',
            default => $value,
        };
    };
    $shortcuts = [
        'Hoy' => [today(), today()],
        'Ayer' => [today()->subDay(), today()->subDay()],
        'Últimos 7 días' => [today()->subDays(6), today()],
        'Este mes' => [today()->startOfMonth(), today()],
        'Mes anterior' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
    ];
@endphp

@section('content')
    <p class="no-print mb-4 max-w-3xl text-sm text-muted">{{ $report->description() }}</p>

    <form method="GET" class="no-print card mb-5 p-4">
        <div class="mb-3 flex flex-wrap gap-1">
            @foreach ($shortcuts as $text => [$d, $h])
                <a href="{{ request()->fullUrlWithQuery(['from' => $d->format('Y-m-d'), 'to' => $h->format('Y-m-d')]) }}"
                   @class(['rounded-md px-3 py-1 text-xs font-semibold transition',
                           'bg-brand-500 text-panel' => $filters->from->isSameDay($d) && $filters->to->isSameDay($h),
                           'bg-slate-100 text-muted hover:text-ink' => ! ($filters->from->isSameDay($d) && $filters->to->isSameDay($h))])>{{ $text }}</a>
            @endforeach
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div><label class="label" for="from">Desde</label><input type="date" name="from" id="from" value="{{ $filters->from->format('Y-m-d') }}" class="field"></div>
            <div><label class="label" for="to">Hasta</label><input type="date" name="to" id="to" value="{{ $filters->to->format('Y-m-d') }}" class="field"></div>
            @if (in_array('branch', $used))
                <div class="w-44"><label class="label" for="branch">Sucursal</label>
                    <select name="branch" id="branch" class="field"><option value="">Todas</option>
                        @foreach ($options['branch'] as $id => $name)<option value="{{ $id }}" @selected($filters->branchId == $id)>{{ $name }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('cajero', $used))
                <div class="w-40"><label class="label" for="cashier">Cajero</label>
                    <select name="cashier" id="cashier" class="field"><option value="">Todos</option>
                        @foreach ($options['cajero'] as $login)<option value="{{ $login }}" @selected($filters->cashier === $login)>{{ $login }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('payment_method', $used))
                <div class="w-40"><label class="label" for="payment_method">Forma de pago</label>
                    <select name="payment_method" id="payment_method" class="field"><option value="">Todas</option>
                        @foreach ($options['payment_method'] as $form)<option value="{{ $form }}" @selected($filters->paymentMethod === $form)>{{ $form }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('category', $used))
                <div class="w-44"><label class="label" for="category">Categoría</label>
                    <select name="category" id="category" class="field"><option value="">Todas</option>
                        @foreach ($options['category'] as $id => $name)<option value="{{ $id }}" @selected($filters->categoryId == $id)>{{ $name }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('shift', $used))
                <div class="w-36"><label class="label" for="shift">Turno</label>
                    <select name="shift" id="shift" class="field"><option value="">Ambos</option>
                        @foreach (['MAÑANA', 'NOCHE'] as $t)<option value="{{ $t }}" @selected($filters->shift === $t)>{{ ucfirst(mb_strtolower($t)) }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('status', $used))
                <div class="w-36"><label class="label" for="status">Estado</label>
                    <select name="status" id="status" class="field"><option value="">Todas</option>
                        <option value="ACTIVE" @selected($filters->status === 'ACTIVE')>Activas</option>
                        <option value="INACTIVE" @selected($filters->status === 'INACTIVE')>Anuladas</option>
                    </select></div>
            @endif
            @if ($report->types())
                <div class="w-44"><label class="label" for="type">Tipo</label>
                    <select name="type" id="type" class="field"><option value="">Todos</option>
                        @foreach ($report->types() as $key => $text)<option value="{{ $key }}" @selected($filters->type === $key)>{{ $text }}</option>@endforeach
                    </select></div>
            @endif
            <button class="btn-primary"><i class="fa fa-search"></i> Generar</button>
            <div class="ml-auto flex gap-2">
                @can('reports.export')
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary"><i class="fa fa-file-excel-o"></i> Excel</a>
                @endcan
                <button type="button" onclick="window.print()" class="btn-secondary"><i class="fa fa-print"></i> Imprimir</button>
            </div>
        </div>
    </form>

    <div class="card overflow-x-auto">
        <div class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-4 pb-3">
            <h2 class="font-bold">{{ $report->title() }}</h2>
            <p class="text-sm text-muted">
                {{ $filters->from->format('d/m/Y') }} al {{ $filters->to->format('d/m/Y') }}
                @if ($filters->branchId) · {{ $options['branch'][$filters->branchId] ?? '' }} @endif
                · {{ count($rows) }} registro(s)
            </p>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($columns as [$label, $format])
                        <th @class(['text-right' => in_array($format, ['currency', 'number', 'percent'])])>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr @if (isset($row['_venta'])) class="cursor-pointer" onclick="location.href='{{ route('admin.sales.show', $row['_venta']) }}'" @endif>
                        @foreach ($columns as $key => [, $format])
                            <td @class(['num' => in_array($format, ['currency', 'number', 'percent']), 'whitespace-nowrap' => $format === 'date'])>{{ $renderValue($row[$key] ?? null, $format) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" class="py-10 text-center text-muted">No hay datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
            @if ($totals && count($rows))
                <tfoot>
                    <tr>
                        @foreach ($columns as $key => [, $format])
                            <td @class(['num' => array_key_exists($key, $totals)])>{{ $loop->first ? 'Totales' : (array_key_exists($key, $totals) ? $renderValue($totals[$key], $format) : '') }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
