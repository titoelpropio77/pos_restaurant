@extends('admin.layout')

@section('title', 'Movimientos de inventario')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-52">
            <label class="label" for="type">Tipo</label>
            <select name="type" id="type" class="field" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($types as $key => $text)<option value="{{ $key }}" @selected(request('type') === $key)>{{ $text }}</option>@endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="label" for="warehouse">Almacén</label>
            <select name="warehouse" id="warehouse" class="field" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($warehouses as $id => $name)<option value="{{ $id }}" @selected(request('warehouse') == $id)>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div><label class="label" for="from">Desde</label><input type="date" name="from" id="from" value="{{ request('from') }}" class="field"></div>
        <div><label class="label" for="to">Hasta</label><input type="date" name="to" id="to" value="{{ request('to') }}" class="field"></div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Filtrar</button>
        @can('inventory.create')
        <a href="{{ route('admin.inventory.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo movimiento</a>
        @endcan
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Nro.</th><th>Fecha</th><th>Tipo</th><th>Almacén</th><th>Motivo / glosa</th><th>Usuario</th><th class="text-right">Ítems</th><th class="text-right">Importe (Bs)</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($movements as $m)
                    <tr>
                        <td class="tabular-nums">{{ $m->id }}</td>
                        <td class="whitespace-nowrap">{{ $m->date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-slate-100 text-slate-700">{{ $types[$m->type] ?? $m->type }}</span></td>
                        <td>{{ $m->warehouse?->name }} @if ($m->destinationWarehouse)<i class="fa fa-long-arrow-right text-muted"></i> {{ $m->destinationWarehouse->name }}@endif</td>
                        <td class="max-w-72 truncate">{{ $m->movementReason?->name }} <span class="text-muted">{{ $m->description }}</span></td>
                        <td>{{ $m->user?->username }}</td>
                        <td class="num">{{ $m->kardex_count }}</td>
                        <td class="num">{{ number_format((float) $m->total, 2) }}</td>
                        <td><a href="{{ route('admin.inventory.show', $m) }}" class="btn-secondary btn-small">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-muted">No hay movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movements->links() }}</div>
@endsection
