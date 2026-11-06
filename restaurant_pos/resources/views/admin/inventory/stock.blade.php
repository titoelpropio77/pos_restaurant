@extends('admin.layout')

@section('title', 'Stock de insumos')

@section('content')
    <form class="no-print mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="label" for="q">Buscar insumo</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field">
        </div>
        <div class="w-full sm:w-52">
            <label class="label" for="warehouse">Almacén</label>
            <select name="warehouse" id="warehouse" class="field" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($allWarehouses as $id => $name)
                    <option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm font-semibold"><input type="checkbox" name="low" value="1" @checked(request('low')) onchange="this.form.submit()" class="accent-brand-500"> Sólo bajo mínimo</label>
        <button class="btn-secondary"><i class="fa fa-search"></i> Filtrar</button>
        <div class="ml-auto flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn-secondary"><i class="fa fa-file-excel-o"></i> Excel</a>
            @can('inventory.create')
            <a href="{{ route('admin.inventory.create') }}" class="btn-primary"><i class="fa fa-plus"></i> Registrar movimiento</a>
            @endcan
        </div>
    </form>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="card p-4"><p class="text-sm text-muted">Insumos</p><p class="mt-1 text-2xl font-bold">{{ $rows->count() }}</p></div>
        <div class="card p-4"><p class="text-sm text-muted">Bajo stock mínimo</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ $rows->where('low', true)->count() }}</p></div>
        <div class="card p-4"><p class="text-sm text-muted">Inventario valorizado</p><p class="mt-1 text-2xl font-bold">Bs {{ number_format($valueTotal, 2) }}</p></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Insumo</th>
                    @foreach ($warehouses as $a)<th class="text-right">{{ $a->name }}</th>@endforeach
                    <th class="text-right">Total</th>
                    <th class="text-right">Mínimo</th>
                    <th class="text-right">Costo prom.</th>
                    <th class="text-right">Valor (Bs)</th>
                    <th class="no-print w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $f)
                    <tr>
                        <td>
                            <span class="font-semibold">{{ $f->supply->supply_name }}</span> <span class="text-xs text-muted">{{ $f->supply->measure }}</span>
                            @if ($f->low)<span class="badge bg-amber-100 text-amber-800"><i class="fa fa-exclamation-triangle"></i> bajo mínimo</span>@endif
                        </td>
                        @foreach ($f->porWarehouse as $quantity)
                            <td @class(['num', 'text-red-600' => $quantity < 0])>{{ rtrim(rtrim(number_format($quantity, 3), '0'), '.') }}</td>
                        @endforeach
                        <td class="num font-bold">{{ rtrim(rtrim(number_format($f->total, 3), '0'), '.') }}</td>
                        <td class="num text-muted">{{ (float) $f->supply->min_stock }}</td>
                        <td class="num">{{ number_format($f->cost, 2) }}</td>
                        <td class="num">{{ number_format($f->value, 2) }}</td>
                        <td class="no-print"><a href="{{ route('admin.inventory.kardex', ['supply' => $f->supply->id]) }}" class="btn-secondary btn-small" title="Kardex"><i class="fa fa-list-alt"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $warehouses->count() + 6 }}" class="py-10 text-center text-muted">No hay insumos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
