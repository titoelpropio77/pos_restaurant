@extends('admin.layout')

@section('titulo', $reporte->titulo())

@php
    $usados = $reporte->filtros();
    $columnas = $reporte->columnas();
    $formatear = function ($valor, $formato) {
        if ($valor === null || $valor === '') return '—';
        return match ($formato) {
            'fecha' => ($valor instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($valor) : \Illuminate\Support\Carbon::parse($valor))->format('d/m/Y'),
            'moneda' => number_format((float) $valor, 2),
            'numero' => rtrim(rtrim(number_format((float) $valor, 3), '0'), '.'),
            'porcentaje' => number_format((float) $valor, 1).'%',
            default => $valor,
        };
    };
    $atajos = [
        'Hoy' => [today(), today()],
        'Ayer' => [today()->subDay(), today()->subDay()],
        'Últimos 7 días' => [today()->subDays(6), today()],
        'Este mes' => [today()->startOfMonth(), today()],
        'Mes anterior' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
    ];
@endphp

@section('contenido')
    <p class="no-imprimir mb-4 max-w-3xl text-sm text-tenue">{{ $reporte->descripcion() }}</p>

    <form method="GET" class="no-imprimir tarjeta mb-5 p-4">
        <div class="mb-3 flex flex-wrap gap-1">
            @foreach ($atajos as $texto => [$d, $h])
                <a href="{{ request()->fullUrlWithQuery(['desde' => $d->format('Y-m-d'), 'hasta' => $h->format('Y-m-d')]) }}"
                   @class(['rounded-md px-3 py-1 text-xs font-semibold transition',
                           'bg-marca-500 text-white' => $filtros->desde->isSameDay($d) && $filtros->hasta->isSameDay($h),
                           'bg-slate-100 text-tenue hover:text-tinta' => ! ($filtros->desde->isSameDay($d) && $filtros->hasta->isSameDay($h))])>{{ $texto }}</a>
            @endforeach
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div><label class="etiqueta" for="desde">Desde</label><input type="date" name="desde" id="desde" value="{{ $filtros->desde->format('Y-m-d') }}" class="campo"></div>
            <div><label class="etiqueta" for="hasta">Hasta</label><input type="date" name="hasta" id="hasta" value="{{ $filtros->hasta->format('Y-m-d') }}" class="campo"></div>
            @if (in_array('sucursal', $usados))
                <div class="w-44"><label class="etiqueta" for="sucursal">Sucursal</label>
                    <select name="sucursal" id="sucursal" class="campo"><option value="">Todas</option>
                        @foreach ($opciones['sucursal'] as $id => $nombre)<option value="{{ $id }}" @selected($filtros->sucursalId == $id)>{{ $nombre }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('cajero', $usados))
                <div class="w-40"><label class="etiqueta" for="cajero">Cajero</label>
                    <select name="cajero" id="cajero" class="campo"><option value="">Todos</option>
                        @foreach ($opciones['cajero'] as $login)<option value="{{ $login }}" @selected($filtros->cajero === $login)>{{ $login }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('formapago', $usados))
                <div class="w-40"><label class="etiqueta" for="formapago">Forma de pago</label>
                    <select name="formapago" id="formapago" class="campo"><option value="">Todas</option>
                        @foreach ($opciones['formapago'] as $forma)<option value="{{ $forma }}" @selected($filtros->formaPago === $forma)>{{ $forma }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('categoria', $usados))
                <div class="w-44"><label class="etiqueta" for="categoria">Categoría</label>
                    <select name="categoria" id="categoria" class="campo"><option value="">Todas</option>
                        @foreach ($opciones['categoria'] as $id => $nombre)<option value="{{ $id }}" @selected($filtros->categoriaId == $id)>{{ $nombre }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('turno', $usados))
                <div class="w-36"><label class="etiqueta" for="turno">Turno</label>
                    <select name="turno" id="turno" class="campo"><option value="">Ambos</option>
                        @foreach (['MAÑANA', 'NOCHE'] as $t)<option value="{{ $t }}" @selected($filtros->turno === $t)>{{ ucfirst(mb_strtolower($t)) }}</option>@endforeach
                    </select></div>
            @endif
            @if (in_array('estado', $usados))
                <div class="w-36"><label class="etiqueta" for="estado">Estado</label>
                    <select name="estado" id="estado" class="campo"><option value="">Todas</option>
                        <option value="ACTIVO" @selected($filtros->estado === 'ACTIVO')>Activas</option>
                        <option value="INACTIVO" @selected($filtros->estado === 'INACTIVO')>Anuladas</option>
                    </select></div>
            @endif
            @if ($reporte->tipos())
                <div class="w-44"><label class="etiqueta" for="tipo">Tipo</label>
                    <select name="tipo" id="tipo" class="campo"><option value="">Todos</option>
                        @foreach ($reporte->tipos() as $clave => $texto)<option value="{{ $clave }}" @selected($filtros->tipo === $clave)>{{ $texto }}</option>@endforeach
                    </select></div>
            @endif
            <button class="btn-primario"><i class="fa fa-search"></i> Generar</button>
            <div class="ml-auto flex gap-2">
                <a href="{{ request()->fullUrlWithQuery(['exportar' => 'csv']) }}" class="btn-secundario"><i class="fa fa-file-excel-o"></i> Excel</a>
                <button type="button" onclick="window.print()" class="btn-secundario"><i class="fa fa-print"></i> Imprimir</button>
            </div>
        </div>
    </form>

    <div class="tarjeta overflow-x-auto">
        <div class="flex flex-wrap items-baseline justify-between gap-2 px-5 pt-4 pb-3">
            <h2 class="font-bold">{{ $reporte->titulo() }}</h2>
            <p class="text-sm text-tenue">
                {{ $filtros->desde->format('d/m/Y') }} al {{ $filtros->hasta->format('d/m/Y') }}
                @if ($filtros->sucursalId) · {{ $opciones['sucursal'][$filtros->sucursalId] ?? '' }} @endif
                · {{ count($filas) }} registro(s)
            </p>
        </div>
        <table class="tabla">
            <thead>
                <tr>
                    @foreach ($columnas as [$etiqueta, $formato])
                        <th @class(['text-right' => in_array($formato, ['moneda', 'numero', 'porcentaje'])])>{{ $etiqueta }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($filas as $fila)
                    <tr @if (isset($fila['_venta'])) class="cursor-pointer" onclick="location.href='{{ route('admin.ventas.show', $fila['_venta']) }}'" @endif>
                        @foreach ($columnas as $clave => [, $formato])
                            <td @class(['num' => in_array($formato, ['moneda', 'numero', 'porcentaje']), 'whitespace-nowrap' => $formato === 'fecha'])>{{ $formatear($fila[$clave] ?? null, $formato) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columnas) }}" class="py-10 text-center text-tenue">No hay datos para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
            @if ($totales && count($filas))
                <tfoot>
                    <tr>
                        @foreach ($columnas as $clave => [, $formato])
                            <td @class(['num' => array_key_exists($clave, $totales)])>{{ $loop->first ? 'Totales' : (array_key_exists($clave, $totales) ? $formatear($totales[$clave], $formato) : '') }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
@endsection
