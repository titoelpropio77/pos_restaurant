@extends('admin.layout')

@section('title', 'Movimiento de inventario #'.$movement_type->id)

@section('content')
    <div class="mb-4 flex gap-2 no-print">
        <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
        <button onclick="window.print()" class="btn-secondary"><i class="fa fa-print"></i> Imprimir</button>
    </div>

    <div class="card mb-5 grid gap-4 p-6 sm:grid-cols-4">
        <div><p class="label">Tipo</p><p class="font-semibold">{{ \App\Services\StockMovement\StockMovementService::TYPES[$movement_type->type] ?? $movement_type->type }}</p></div>
        <div><p class="label">Fecha</p><p class="font-semibold">{{ $movement_type->date->format('d/m/Y') }} {{ substr((string) $movement_type->time, 0, 5) }}</p></div>
        <div><p class="label">Almacén</p><p class="font-semibold">{{ $movement_type->warehouse?->name }} @if ($movement_type->destinationWarehouse) → {{ $movement_type->destinationWarehouse->name }}@endif</p></div>
        <div><p class="label">Registrado por</p><p class="font-semibold">{{ $movement_type->username?->name ?? '—' }}</p></div>
        <div><p class="label">Motivo</p><p>{{ $movement_type->movementReason?->name ?? '—' }}</p></div>
        <div class="sm:col-span-2"><p class="label">Glosa</p><p>{{ $movement_type->description ?: '—' }}</p></div>
        <div><p class="label">Importe</p><p class="text-xl font-bold">Bs {{ number_format((float) $movement_type->total, 2) }}</p></div>
    </div>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Insumo</th><th>Almacén</th><th>Movimiento</th><th class="text-right">Entrada</th><th class="text-right">Salida</th><th class="text-right">Saldo</th><th class="text-right">Costo unit.</th></tr></thead>
            <tbody>
                @foreach ($movement_type->kardex as $k)
                    <tr>
                        <td class="font-semibold">{{ $k->supply_name }} <span class="text-xs text-muted">{{ $k->unit }}</span></td>
                        <td>{{ $k->warehouse?->name }}</td>
                        <td>{{ $k->movement_type }}</td>
                        <td class="num text-green-700">{{ (float) $k->qty_in ?: '' }}</td>
                        <td class="num text-red-600">{{ (float) $k->qty_out ?: '' }}</td>
                        <td class="num font-semibold">{{ (float) $k->balance }}</td>
                        <td class="num">{{ number_format((float) $k->cost_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
