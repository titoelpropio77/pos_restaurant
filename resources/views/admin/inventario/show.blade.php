@extends('admin.layout')

@section('titulo', 'Movimiento de inventario #'.$movimiento->id)

@section('contenido')
    <div class="mb-4 flex gap-2 no-imprimir">
        <a href="{{ route('admin.inventario.movimientos') }}" class="btn-secundario"><i class="fa fa-arrow-left"></i> Volver</a>
        <button onclick="window.print()" class="btn-secundario"><i class="fa fa-print"></i> Imprimir</button>
    </div>

    <div class="tarjeta mb-5 grid gap-4 p-6 sm:grid-cols-4">
        <div><p class="etiqueta">Tipo</p><p class="font-semibold">{{ \App\Services\Inventario\MovimientoInventario::TIPOS[$movimiento->tipo] ?? $movimiento->tipo }}</p></div>
        <div><p class="etiqueta">Fecha</p><p class="font-semibold">{{ $movimiento->fecha->format('d/m/Y') }} {{ substr((string) $movimiento->hora, 0, 5) }}</p></div>
        <div><p class="etiqueta">Almacén</p><p class="font-semibold">{{ $movimiento->almacen?->nombre }} @if ($movimiento->almacenDestino) → {{ $movimiento->almacenDestino->nombre }}@endif</p></div>
        <div><p class="etiqueta">Registrado por</p><p class="font-semibold">{{ $movimiento->usuario?->nombres ?? '—' }}</p></div>
        <div><p class="etiqueta">Motivo</p><p>{{ $movimiento->motivoMovimiento?->nombre ?? '—' }}</p></div>
        <div class="sm:col-span-2"><p class="etiqueta">Glosa</p><p>{{ $movimiento->glosa ?: '—' }}</p></div>
        <div><p class="etiqueta">Importe</p><p class="text-xl font-bold">Bs {{ number_format((float) $movimiento->importe, 2) }}</p></div>
    </div>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Insumo</th><th>Almacén</th><th>Movimiento</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-right">Saldo</th><th class="text-right">Costo unit.</th></tr></thead>
            <tbody>
                @foreach ($movimiento->kardex as $k)
                    <tr>
                        <td class="font-semibold">{{ $k->nom_insumo }} <span class="text-xs text-tenue">{{ $k->unidad }}</span></td>
                        <td>{{ $k->almacen?->nombre }}</td>
                        <td>{{ $k->movimiento }}</td>
                        <td class="num text-green-700">{{ (float) $k->entrada ?: '' }}</td>
                        <td class="num text-red-600">{{ (float) $k->salida ?: '' }}</td>
                        <td class="num font-semibold">{{ (float) $k->saldo }}</td>
                        <td class="num">{{ number_format((float) $k->pre_costo, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
