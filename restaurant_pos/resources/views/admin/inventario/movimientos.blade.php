@extends('admin.layout')

@section('titulo', 'Movimientos de inventario')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-52">
            <label class="etiqueta" for="tipo">Tipo</label>
            <select name="tipo" id="tipo" class="campo" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($tipos as $clave => $texto)<option value="{{ $clave }}" @selected(request('tipo') === $clave)>{{ $texto }}</option>@endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="etiqueta" for="almacen">Almacén</label>
            <select name="almacen" id="almacen" class="campo" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($almacenes as $id => $nombre)<option value="{{ $id }}" @selected(request('almacen') == $id)>{{ $nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="etiqueta" for="desde">Desde</label><input type="date" name="desde" id="desde" value="{{ request('desde') }}" class="campo"></div>
        <div><label class="etiqueta" for="hasta">Hasta</label><input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}" class="campo"></div>
        <button class="btn-secundario"><i class="fa fa-search"></i> Filtrar</button>
        <a href="{{ route('admin.inventario.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo movimiento</a>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Nro.</th><th>Fecha</th><th>Tipo</th><th>Almacén</th><th>Motivo / glosa</th><th>Usuario</th><th class="text-right">Ítems</th><th class="text-right">Importe (Bs)</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($movimientos as $m)
                    <tr>
                        <td class="tabular-nums">{{ $m->id }}</td>
                        <td class="whitespace-nowrap">{{ $m->fecha->format('d/m/Y') }}</td>
                        <td><span class="insignia bg-slate-100 text-slate-700">{{ $tipos[$m->tipo] ?? $m->tipo }}</span></td>
                        <td>{{ $m->almacen?->nombre }} @if ($m->almacenDestino)<i class="fa fa-long-arrow-right text-tenue"></i> {{ $m->almacenDestino->nombre }}@endif</td>
                        <td class="max-w-72 truncate">{{ $m->motivoMovimiento?->nombre }} <span class="text-tenue">{{ $m->glosa }}</span></td>
                        <td>{{ $m->usuario?->usuario }}</td>
                        <td class="num">{{ $m->kardex_count }}</td>
                        <td class="num">{{ number_format((float) $m->importe, 2) }}</td>
                        <td><a href="{{ route('admin.inventario.show', $m) }}" class="btn-secundario btn-chico">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="py-10 text-center text-tenue">No hay movimientos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $movimientos->links() }}</div>
@endsection
