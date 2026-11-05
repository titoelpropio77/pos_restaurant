@extends('admin.layout')

@section('titulo', 'Kardex de insumos')

@section('contenido')
    <form class="no-imprimir mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="insumo">Insumo</label>
            <select name="insumo" id="insumo" class="campo" required>
                <option value="">Seleccione...</option>
                @foreach ($insumos as $id => $nombre)<option value="{{ $id }}" @selected($insumo?->id == $id)>{{ $nombre }}</option>@endforeach
            </select>
        </div>
        <div class="w-full sm:w-48">
            <label class="etiqueta" for="almacen">Almacén</label>
            <select name="almacen" id="almacen" class="campo">
                <option value="">Todos</option>
                @foreach ($almacenes as $id => $nombre)<option value="{{ $id }}" @selected(request('almacen') == $id)>{{ $nombre }}</option>@endforeach
            </select>
        </div>
        <div><label class="etiqueta" for="desde">Desde</label><input type="date" name="desde" id="desde" value="{{ $desde->format('Y-m-d') }}" class="campo"></div>
        <div><label class="etiqueta" for="hasta">Hasta</label><input type="date" name="hasta" id="hasta" value="{{ $hasta->format('Y-m-d') }}" class="campo"></div>
        <button class="btn-primario"><i class="fa fa-search"></i> Consultar</button>
        @if ($insumo)
            <div class="ml-auto flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['exportar' => 'csv']) }}" class="btn-secundario"><i class="fa fa-file-excel-o"></i> Excel</a>
                <button type="button" onclick="window.print()" class="btn-secundario"><i class="fa fa-print"></i> Imprimir</button>
            </div>
        @endif
    </form>

    @if (! $insumo)
        <div class="tarjeta p-10 text-center text-tenue"><i class="fa fa-list-alt mb-2 text-3xl"></i><p>Seleccione un insumo para ver sus entradas, salidas y saldo.</p></div>
    @else
        <div class="tarjeta overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $insumo->nom_insumo }} <span class="text-sm font-normal text-tenue">({{ $insumo->medida }}) · {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }}</span></h2>
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Almacén</th><th>Movimiento</th><th>Detalle</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-right">Saldo</th><th class="text-right">Costo unit.</th></tr></thead>
                <tbody>
                    @forelse ($movimientos as $k)
                        <tr>
                            <td class="whitespace-nowrap">{{ $k->fecha_mov->format('d/m/Y') }}</td>
                            <td>{{ $k->almacen?->nombre ?? '—' }}</td>
                            <td>
                                @if ($k->inventario_id)<a href="{{ route('admin.inventario.show', $k->inventario_id) }}" class="font-semibold text-marca-600 hover:underline">{{ $k->movimiento }}</a>@else{{ $k->movimiento }}@endif
                                @if ($k->venta_id)<a href="{{ route('admin.ventas.show', $k->venta_id) }}" class="text-xs text-tenue hover:underline">venta #{{ $k->venta_id }}</a>@endif
                            </td>
                            <td class="max-w-64 truncate text-tenue">{{ $k->pormenores ?: $k->inventario?->glosa }}</td>
                            <td class="num text-green-700">{{ (float) $k->entrada ?: '' }}</td>
                            <td class="num text-red-600">{{ (float) $k->salida ?: '' }}</td>
                            <td class="num font-semibold">{{ (float) $k->saldo }}</td>
                            <td class="num">{{ number_format((float) $k->pre_costo, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-10 text-center text-tenue">Sin movimientos en el período.</td></tr>
                    @endforelse
                </tbody>
                @if ($movimientos->isNotEmpty())
                    <tfoot><tr><td colspan="4">Totales del período</td><td class="num">{{ (float) $movimientos->sum('entrada') }}</td><td class="num">{{ (float) $movimientos->sum('salida') }}</td><td colspan="2"></td></tr></tfoot>
                @endif
            </table>
        </div>
    @endif
@endsection
