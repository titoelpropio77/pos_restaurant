@extends('admin.layout')

@section('title', 'Kardex de insumos')

@section('content')
    <form class="no-print mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="supply">Insumo</label>
            <select name="supply" id="supply" class="field" required>
                <option value="">Seleccione...</option>
                @foreach ($supplies as $id => $name)<option value="{{ $id }}" @selected($supply?->id == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="label" for="warehouse">Almacén</label>
            <select name="warehouse" id="warehouse" class="field">
                <option value="">Todos</option>
                @foreach ($warehouses as $id => $name)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div><label class="label" for="from">Desde</label><input type="date" name="from" id="from" value="{{ $from->format('Y-m-d') }}" class="field"></div>
        <div><label class="label" for="to">Hasta</label><input type="date" name="to" id="to" value="{{ $to->format('Y-m-d') }}" class="field"></div>
        <button class="btn-primary"><i class="fa fa-search"></i> Consultar</button>
        @if ($supply)
            <div class="ml-auto flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary"><i class="fa fa-file-excel-o"></i> Excel</a>
                <button type="button" onclick="window.print()" class="btn-secondary"><i class="fa fa-print"></i> Imprimir</button>
            </div>
        @endif
    </form>

    @if (! $supply)
        <div class="card p-10 text-center text-muted"><i class="fa fa-list-alt mb-2 text-3xl"></i><p>Seleccione un insumo para ver sus entradas, salidas y saldo.</p></div>
    @else
        <div class="card overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $supply->supply_name }} <span class="text-sm font-normal text-muted">({{ $supply->measure }}) · {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</span></h2>
            <table class="data-table">
                <thead><tr><th>Fecha</th><th>Almacén</th><th>Movimiento</th><th>Detalle</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-right">Saldo</th><th class="text-right">Costo unit.</th></tr></thead>
                <tbody>
                    @forelse ($movements as $k)
                        <tr>
                            <td class="whitespace-nowrap">{{ $k->movement_date->format('d/m/Y') }}</td>
                            <td>{{ $k->warehouse?->name ?? '—' }}</td>
                            <td>
                                @if ($k->stock_movement_id)<a href="{{ route('admin.inventory.show', $k->stock_movement_id) }}" class="font-semibold text-brand-600 hover:underline">{{ $k->movement_type }}</a>@else{{ $k->movement_type }}@endif
                                @if ($k->sale_id)<a href="{{ route('admin.sales.show', $k->sale_id) }}" class="text-xs text-muted hover:underline">venta #{{ $k->sale_id }}</a>@endif
                            </td>
                            <td class="max-w-64 truncate text-muted">{{ $k->details ?: $k->inventory?->description }}</td>
                            <td class="num text-green-700">{{ (float) $k->qty_in ?: '' }}</td>
                            <td class="num text-red-600">{{ (float) $k->qty_out ?: '' }}</td>
                            <td class="num font-semibold">{{ (float) $k->balance }}</td>
                            <td class="num">{{ number_format((float) $k->cost_price, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-muted">Sin movimientos en el período.</td></tr>
                    @endforelse
                </tbody>
                @if ($movements->isNotEmpty())
                    <tfoot><tr><td colspan="4">Totales del período</td><td class="num">{{ (float) $movements->sum('qty_in') }}</td><td class="num">{{ (float) $movements->sum('qty_out') }}</td><td colspan="2"></td></tr></tfoot>
                @endif
            </table>
        </div>
    @endif
@endsection
