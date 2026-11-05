@extends('admin.layout')

@section('titulo', 'Stock de insumos')

@section('contenido')
    <form class="no-imprimir mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="etiqueta" for="q">Buscar insumo</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo">
        </div>
        <div class="w-full sm:w-52">
            <label class="etiqueta" for="almacen">Almacén</label>
            <select name="almacen" id="almacen" class="campo" onchange="this.form.submit()">
                <option value="">Todos</option>
                @foreach ($todosAlmacenes as $id => $nombre)
                    <option value="{{ $id }}" @selected(request('almacen') == $id)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 pb-2 text-sm font-semibold"><input type="checkbox" name="bajos" value="1" @checked(request('bajos')) onchange="this.form.submit()" class="accent-marca-500"> Sólo bajo mínimo</label>
        <button class="btn-secundario"><i class="fa fa-search"></i> Filtrar</button>
        <div class="ml-auto flex gap-2">
            <a href="{{ request()->fullUrlWithQuery(['exportar' => 'csv']) }}" class="btn-secundario"><i class="fa fa-file-excel-o"></i> Excel</a>
            <a href="{{ route('admin.inventario.create') }}" class="btn-primario"><i class="fa fa-plus"></i> Registrar movimiento</a>
        </div>
    </form>

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="tarjeta p-4"><p class="text-sm text-tenue">Insumos</p><p class="mt-1 text-2xl font-bold">{{ $filas->count() }}</p></div>
        <div class="tarjeta p-4"><p class="text-sm text-tenue">Bajo stock mínimo</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ $filas->where('bajo', true)->count() }}</p></div>
        <div class="tarjeta p-4"><p class="text-sm text-tenue">Inventario valorizado</p><p class="mt-1 text-2xl font-bold">Bs {{ number_format($valorTotal, 2) }}</p></div>
    </div>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Insumo</th>
                    @foreach ($almacenes as $a)<th class="text-right">{{ $a->nombre }}</th>@endforeach
                    <th class="text-right">Total</th>
                    <th class="text-right">Mínimo</th>
                    <th class="text-right">Costo prom.</th>
                    <th class="text-right">Valor (Bs)</th>
                    <th class="no-imprimir w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $f)
                    <tr>
                        <td>
                            <span class="font-semibold">{{ $f->insumo->nom_insumo }}</span> <span class="text-xs text-tenue">{{ $f->insumo->medida }}</span>
                            @if ($f->bajo)<span class="insignia bg-amber-100 text-amber-800"><i class="fa fa-exclamation-triangle"></i> bajo mínimo</span>@endif
                        </td>
                        @foreach ($f->porAlmacen as $cantidad)
                            <td @class(['num', 'text-red-600' => $cantidad < 0])>{{ rtrim(rtrim(number_format($cantidad, 3), '0'), '.') }}</td>
                        @endforeach
                        <td class="num font-bold">{{ rtrim(rtrim(number_format($f->total, 3), '0'), '.') }}</td>
                        <td class="num text-tenue">{{ (float) $f->insumo->stock_min }}</td>
                        <td class="num">{{ number_format($f->costo, 2) }}</td>
                        <td class="num">{{ number_format($f->valor, 2) }}</td>
                        <td class="no-imprimir"><a href="{{ route('admin.inventario.kardex', ['insumo' => $f->insumo->id]) }}" class="btn-secundario btn-chico" title="Kardex"><i class="fa fa-list-alt"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $almacenes->count() + 6 }}" class="py-10 text-center text-tenue">No hay insumos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
