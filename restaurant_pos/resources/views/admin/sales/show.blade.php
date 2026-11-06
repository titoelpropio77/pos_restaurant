@extends('admin.layout')

@section('title', 'Venta · orden #'.$sale->order_number)

@section('content')
    <div class="no-print mb-4 flex flex-wrap gap-2">
        <a href="{{ url()->previous() }}" class="btn-secondary"><i class="fa fa-arrow-left"></i> Volver</a>
        <button onclick="window.print()" class="btn-secondary"><i class="fa fa-print"></i> Imprimir</button>
        @if ($sale->cashSession?->status === \App\Models\CashSession::CLOSED)
            <span class="ml-auto text-sm text-muted"><i class="fa fa-lock"></i> Caja cerrada: no se puede modificar</span>
        @else
        @can('sales.void')
        <form method="POST" action="{{ route('admin.sales.void', $sale) }}" class="ml-auto"
              onsubmit="return confirm('{{ $sale->status === 'ACTIVE' ? '¿Anular esta venta?' : '¿Reactivar esta venta?' }}')">
            @csrf
            @if ($sale->status === 'ACTIVE')
                <button class="btn-danger"><i class="fa fa-ban"></i> Anular venta</button>
            @else
                <button class="btn-secondary"><i class="fa fa-undo"></i> Reactivar venta</button>
            @endif
        </form>
        @endcan
        @endif
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="card overflow-x-auto lg:col-span-2">
            <table class="data-table">
                <thead><tr><th class="text-right">Cant.</th><th>Producto</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach ($sale->details as $d)
                        <tr @class(['text-red-600 line-through' => $d->voided === 'S'])>
                            <td class="num">{{ (float) $d->quantity }}</td>
                            <td>{{ $d->product_name }} @if ($d->note)<span class="block text-xs text-muted">{{ $d->note }}</span>@endif</td>
                            <td class="num">{{ number_format((float) $d->sale_price, 2) }}</td>
                            <td class="num">{{ number_format((float) $d->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="3">Total</td><td class="num text-lg">Bs {{ number_format((float) $sale->invoice_total, 2) }}</td></tr></tfoot>
            </table>
        </div>

        <div class="card space-y-3 p-6 text-sm">
            <p>
                @if ($sale->status === 'ACTIVE')
                    <span class="badge bg-green-100 text-green-800"><i class="fa fa-check-circle"></i> Activa</span>
                @else
                    <span class="badge bg-red-100 text-red-700"><i class="fa fa-ban"></i> Anulada</span> @if ($sale->voided_by)<span class="text-muted">por {{ $sale->voided_by }}</span>@endif
                @endif
            </p>
            @foreach ([
                'Fecha' => $sale->sale_date->format('d/m/Y').' '.$sale->sale_time,
                'Sucursal' => $sale->branch?->name,
                'Cajero' => $sale->login,
                'Turno' => $sale->shift,
                'Forma de pago' => $sale->payment_method,
                'Salida' => $sale->service_type,
                'Cliente' => $sale->customer_name,
                'NIT' => $sale->customer_tax_id,
                'Nro. factura' => $sale->invoice_number ?: '—',
                'Nro. recibo' => $sale->real_invoice_number ?: '—',
                'Autorización' => $sale->authorization_number,
                'Código de control' => $sale->control_code,
                'Pagó (Bs)' => number_format((float) $sale->paid_bob, 2),
                'Cambio (Bs)' => number_format((float) $sale->change_bob, 2),
                'Tipo de cambio' => $sale->exchange_rate,
            ] as $label => $value)
                <div class="flex justify-between gap-4 border-b border-line pb-2 last:border-0"><span class="text-muted">{{ $label }}</span><span class="text-right font-semibold">{{ $value }}</span></div>
            @endforeach
        </div>
    </div>

    @if ($sale->consumptionsCard->isNotEmpty())
        <div class="card mt-5 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">Consumos con esta tarjeta prepago · saldo Bs {{ number_format((float) $sale->prepaid_balance, 2) }}</h2>
            <table class="data-table">
                <thead><tr><th>Fecha</th><th class="text-right">Cant.</th><th>Producto</th><th class="text-right">Importe</th></tr></thead>
                <tbody>
                    @foreach ($sale->consumptionsCard as $c)
                        <tr><td>{{ $c->sale_date->format('d/m/Y') }}</td><td class="num">{{ (float) $c->quantity }}</td><td>{{ $c->product_name }}</td><td class="num">{{ number_format($c->quantity * $c->sale_price, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
