{{-- Precuenta de una mesa: detalle con precios, no es comprobante. --}}
@extends('pos.tickets.layout')

@section('title', 'Precuenta')
@section('empty', $items->isEmpty() ? '1' : '0')

@section('content')
    <div class="ticket">
        <div class="center bold big">{{ $business['name'] }}</div>
        <div class="center small">{{ $business['branch'] }}</div>
        @if ($business['address'])<div class="center small">{{ $business['address'] }}</div>@endif
        @if ($business['phone'])<div class="center small">Tel. {{ $business['phone'] }}</div>@endif
        <hr class="rule">
        <div class="center bold inverse">PRECUENTA</div>
        <div class="center huge" style="margin-top: 1mm;">{{ $table }}</div>
        @if ($customer)<div class="center bold">{{ $customer }}</div>@endif
        <hr class="rule">
        @if ($waiter)<div>Mesero: <span class="bold">{{ $waiter }}</span></div>@endif
        @if ($people)<div>Personas: {{ $people }}</div>@endif
        <div class="small">{{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }} {{ $printedAt->format('H:i') }}</div>
        <hr class="rule-solid">
        <table>
            @foreach ($items as $item)
                <tr>
                    <td class="qty">{{ rtrim(rtrim(number_format($item['quantity'], 2, '.', ''), '0'), '.') }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td class="money">{{ number_format($item['subtotal'], 2) }}</td>
                </tr>
                @if ($item['quantity'] != 1)
                    <tr><td></td><td class="small" colspan="2">a {{ number_format($item['price'], 2) }} c/u</td></tr>
                @endif
            @endforeach
        </table>
        <hr class="rule-solid">
        <table>
            <tr class="big"><td>TOTAL Bs</td><td class="money">{{ number_format($total, 2) }}</td></tr>
        </table>
        <hr class="rule">
        <div class="center bold">NO VÁLIDO COMO FACTURA</div>
        <div class="center small">Gracias por su preferencia</div>
    </div>
@endsection
