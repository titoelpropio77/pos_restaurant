@extends('admin.layout')

@section('titulo', 'Venta · orden #'.$venta->orden)

@section('contenido')
    <div class="no-imprimir mb-4 flex flex-wrap gap-2">
        <a href="{{ url()->previous() }}" class="btn-secundario"><i class="fa fa-arrow-left"></i> Volver</a>
        <button onclick="window.print()" class="btn-secundario"><i class="fa fa-print"></i> Imprimir</button>
        <form method="POST" action="{{ route('admin.ventas.anular', $venta) }}" class="ml-auto"
              onsubmit="return confirm('{{ $venta->estado === 'ACTIVO' ? '¿Anular esta venta?' : '¿Reactivar esta venta?' }}')">
            @csrf
            @if ($venta->estado === 'ACTIVO')
                <button class="btn-peligro"><i class="fa fa-ban"></i> Anular venta</button>
            @else
                <button class="btn-secundario"><i class="fa fa-undo"></i> Reactivar venta</button>
            @endif
        </form>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="tarjeta overflow-x-auto lg:col-span-2">
            <table class="tabla">
                <thead><tr><th class="text-right">Cant.</th><th>Producto</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>
                <tbody>
                    @foreach ($venta->detalles as $d)
                        <tr @class(['text-red-600 line-through' => $d->anulado === 'S'])>
                            <td class="num">{{ (float) $d->cantidad }}</td>
                            <td>{{ $d->nom_prod }} @if ($d->nota)<span class="block text-xs text-tenue">{{ $d->nota }}</span>@endif</td>
                            <td class="num">{{ number_format((float) $d->pre_venta, 2) }}</td>
                            <td class="num">{{ number_format((float) $d->agrupa1, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot><tr><td colspan="3">Total</td><td class="num text-lg">Bs {{ number_format((float) $venta->total_fact, 2) }}</td></tr></tfoot>
            </table>
        </div>

        <div class="tarjeta space-y-3 p-6 text-sm">
            <p>
                @if ($venta->estado === 'ACTIVO')
                    <span class="insignia bg-green-100 text-green-800"><i class="fa fa-check-circle"></i> Activa</span>
                @else
                    <span class="insignia bg-red-100 text-red-700"><i class="fa fa-ban"></i> Anulada</span> @if ($venta->eliminpor)<span class="text-tenue">por {{ $venta->eliminpor }}</span>@endif
                @endif
            </p>
            @foreach ([
                'Fecha' => $venta->f_venta->format('d/m/Y').' '.$venta->dhoraven,
                'Sucursal' => $venta->sucursal?->nombre,
                'Cajero' => $venta->login,
                'Turno' => $venta->turno,
                'Forma de pago' => $venta->formapago,
                'Salida' => $venta->salida,
                'Cliente' => $venta->nom_cli,
                'NIT' => $venta->nit_cli,
                'Nro. factura' => $venta->fortax ?: '—',
                'Nro. recibo' => $venta->tot_des ?: '—',
                'Autorización' => $venta->nroautori,
                'Código de control' => $venta->cifrado,
                'Pagó (Bs)' => number_format((float) $venta->pago_bs, 2),
                'Cambio (Bs)' => number_format((float) $venta->cambio_bs, 2),
                'Tipo de cambio' => $venta->cam_of,
            ] as $etiqueta => $valor)
                <div class="flex justify-between gap-4 border-b border-borde pb-2 last:border-0"><span class="text-tenue">{{ $etiqueta }}</span><span class="text-right font-semibold">{{ $valor }}</span></div>
            @endforeach
        </div>
    </div>

    @if ($venta->consumosTarjeta->isNotEmpty())
        <div class="tarjeta mt-5 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">Consumos con esta tarjeta prepago · saldo Bs {{ number_format((float) $venta->pre_pago, 2) }}</h2>
            <table class="tabla">
                <thead><tr><th>Fecha</th><th class="text-right">Cant.</th><th>Producto</th><th class="text-right">Importe</th></tr></thead>
                <tbody>
                    @foreach ($venta->consumosTarjeta as $c)
                        <tr><td>{{ $c->f_venta->format('d/m/Y') }}</td><td class="num">{{ (float) $c->cantidad }}</td><td>{{ $c->nom_prod }}</td><td class="num">{{ number_format($c->cantidad * $c->pre_venta, 2) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
