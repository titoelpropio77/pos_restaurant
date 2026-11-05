@extends('admin.layout')

@section('titulo', 'Inicio')

@section('contenido')
    {{-- Filtros: afectan a todo lo que está debajo --}}
    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <div class="flex max-w-full overflow-x-auto rounded-lg bg-white p-1 whitespace-nowrap shadow-sm ring-1 ring-black/5">
            @foreach (\App\Http\Controllers\Admin\DashboardController::PERIODOS as $clave => $texto)
                <a href="{{ route('admin.dashboard', ['periodo' => $clave, 'sucursal' => $sucursalId]) }}"
                   @class(['rounded-md px-3 py-1.5 text-sm font-semibold transition',
                           'bg-marca-500 text-white' => $periodo === (string) $clave,
                           'text-tenue hover:text-tinta' => $periodo !== (string) $clave])>{{ $texto }}</a>
            @endforeach
        </div>
        <input type="hidden" name="periodo" value="{{ $periodo }}">
        <select name="sucursal" class="campo w-auto" onchange="this.form.submit()">
            <option value="">Todas las sucursales</option>
            @foreach ($sucursales as $id => $nombre)
                <option value="{{ $id }}" @selected($sucursalId == $id)>{{ $nombre }}</option>
            @endforeach
        </select>
    </form>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($indicadores as [$etiqueta, $valor, $icono, $ayuda])
            <div class="tarjeta p-4">
                <div class="flex items-center justify-between text-sm text-tenue">
                    {{ $etiqueta }}
                    <i class="fa {{ $icono }} text-slate-400"></i>
                </div>
                <p @class(['mt-2 font-bold tabular-nums', 'text-3xl' => $loop->first, 'text-2xl' => ! $loop->first])>{{ $valor }}</p>
                <p class="mt-1 text-xs text-tenue">{{ $ayuda }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-5 grid gap-5 xl:grid-cols-3">
        <x-admin.grafico-columnas :serie="$serie" :titulo="$tituloSerie" class="xl:col-span-2" />

        <div class="tarjeta p-5">
            <h2 class="mb-3 font-bold">Alertas</h2>
            <ul class="space-y-3 text-sm">
                <li class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100"><i class="fa fa-square-o"></i></span>
                    <span><strong>{{ $mesasOcupadas }}</strong> mesa(s) ocupada(s) en este momento</span>
                </li>
                @foreach ($dosificaciones as [$sucursal, $dias])
                    <li class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-700"><i class="fa fa-exclamation-triangle"></i></span>
                        <span>
                            <strong>{{ $sucursal->nombre }}:</strong>
                            @if ($dias === null) sin dosificación registrada
                            @elseif ($dias < 0) la dosificación venció hace {{ abs($dias) }} día(s)
                            @else la dosificación vence en {{ $dias }} día(s)
                            @endif
                            <a href="{{ route('admin.facturacion.edit', $sucursal) }}" class="font-semibold text-marca-600 hover:underline">Actualizar</a>
                        </span>
                    </li>
                @endforeach
                @forelse ($stockBajo as $insumo)
                    <li class="flex items-center gap-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i class="fa fa-cubes"></i></span>
                        <span><strong>{{ $insumo->nom_insumo }}</strong>: stock {{ rtrim(rtrim(number_format((float) $insumo->stock_total, 2), '0'), '.') }} {{ $insumo->medida }} (mínimo {{ (float) $insumo->stock_min }})</span>
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
        <div class="tarjeta overflow-hidden">
            <h2 class="px-5 pt-5 pb-3 font-bold">Productos más vendidos</h2>
            @php $maxCantidad = max(1, (float) $topProductos->max('cantidad')); @endphp
            <table class="tabla">
                <thead><tr><th>Producto</th><th class="text-right">Cant.</th><th class="text-right">Bs</th></tr></thead>
                <tbody>
                    @forelse ($topProductos as $p)
                        <tr>
                            <td>
                                {{ $p->nom_prod }}
                                <div class="mt-1 h-1.5 rounded-full bg-marca-100"><div class="h-1.5 rounded-full bg-marca-500" style="width: {{ $p->cantidad / $maxCantidad * 100 }}%"></div></div>
                            </td>
                            <td class="num">{{ (float) $p->cantidad }}</td>
                            <td class="num">{{ number_format((float) $p->importe, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-tenue">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="tarjeta overflow-hidden">
            <h2 class="px-5 pt-5 pb-3 font-bold">Formas de pago</h2>
            @php $totalPagos = max(0.01, (float) $formasPago->sum('total')); @endphp
            <table class="tabla">
                <thead><tr><th>Forma de pago</th><th class="text-right">Ventas</th><th class="text-right">Bs</th><th class="text-right">%</th></tr></thead>
                <tbody>
                    @forelse ($formasPago as $f)
                        <tr>
                            <td class="font-semibold">{{ $f->formapago }}</td>
                            <td class="num">{{ $f->ventas }}</td>
                            <td class="num">{{ number_format((float) $f->total, 2) }}</td>
                            <td class="num">{{ number_format($f->total / $totalPagos * 100, 1) }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-tenue">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="tarjeta overflow-hidden">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Últimas ventas</h2>
                <a href="{{ route('admin.reportes.show', 'ventas') }}" class="text-sm font-semibold text-marca-600 hover:underline">Ver todas</a>
            </div>
            <table class="tabla">
                <thead><tr><th>Orden</th><th>Cliente</th><th class="text-right">Bs</th></tr></thead>
                <tbody>
                    @forelse ($ultimasVentas as $v)
                        <tr class="cursor-pointer" onclick="location.href='{{ route('admin.ventas.show', $v) }}'">
                            <td><span class="font-semibold">#{{ $v->orden }}</span> <span class="text-xs text-tenue">{{ $v->dhoraven }}</span></td>
                            <td class="max-w-36 truncate">{{ $v->nom_cli }}</td>
                            <td @class(['num', 'text-red-600 line-through' => $v->estado !== 'ACTIVO'])>{{ number_format((float) $v->total_fact, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-tenue">Sin ventas en el período</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
