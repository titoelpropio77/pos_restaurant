{{-- Nota de venta (sin facturación SIN) o factura (venta con código de control). --}}
@extends('pos.tickets.layout')

@section('title', $title)

@section('content')
    <div class="ticket">
        <div class="center bold big">{{ $business['name'] }}</div>
        @if ($business['owner'])<div class="center small">De: {{ $business['owner'] }}</div>@endif
        <div class="center small">{{ $business['branch'] }}</div>
        @if ($business['address'])<div class="center small">{{ $business['address'] }}</div>@endif
        @if ($business['phone'])<div class="center small">Tel. {{ $business['phone'] }}</div>@endif
        @if ($business['city'])<div class="center small">{{ $business['city'] }}</div>@endif
        <hr class="rule">

        <div class="center bold inverse">{{ $title }}</div>
        @if ($copy)<div class="center bold">*** COPIA ***</div>@endif
        @if ($voided)<div class="center bold big">*** ANULADA ***</div>@endif
        @if ($fiscal)
            <table class="small" style="margin-top: 1mm;">
                @if ($business['tax_id'])<tr><td>NIT</td><td class="right">{{ $business['tax_id'] }}</td></tr>@endif
                <tr><td>Factura N°</td><td class="right bold">{{ $fiscal['number'] }}</td></tr>
                <tr><td>Autorización</td><td class="right">{{ $fiscal['authorization'] }}</td></tr>
            </table>
        @else
            <div class="center big" style="margin-top: 1mm;">N° {{ $number }}</div>
        @endif
        <hr class="rule">

        <div>Fecha: {{ $date }} {{ $time }}</div>
        <div>Orden: <span class="bold">{{ $order }}</span>@if ($service) · {{ $service }}@endif</div>
        @if ($customer || $customer_tax_id || $fiscal)
            <div>Cliente: {{ $customer ?? 'SIN NOMBRE' }}</div>
        @endif
        @if ($customer_tax_id || $fiscal)
            <div>NIT/CI: {{ $customer_tax_id ?? '0' }}</div>
        @endif
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
        <div class="small">Son: {{ $words }}</div>
        <hr class="rule">
        <table class="small">
            <tr><td>Forma de pago</td><td class="right">{{ $payment }}</td></tr>
            @if ($paid > 0)
                <tr><td>Recibido Bs</td><td class="right">{{ number_format($paid, 2) }}</td></tr>
                <tr><td>Cambio Bs</td><td class="right">{{ number_format($change, 2) }}</td></tr>
            @endif
        </table>

        @if ($fiscal)
            <hr class="rule">
            <div class="small">Código de control: <span class="bold">{{ $fiscal['control_code'] }}</span></div>
            @if ($fiscal['limit_date'])<div class="small">Fecha límite de emisión: {{ $fiscal['limit_date'] }}</div>@endif
            <div class="qr" style="margin: 2mm auto; width: {{ $width === 58 ? 30 : 36 }}mm;">{!! $fiscal['qr'] !!}</div>
            @foreach ($business['legends'] as $legend)
                <div class="center small">{{ $legend }}</div>
            @endforeach
        @else
            <hr class="rule">
            <div class="center small">Este documento no es una factura</div>
        @endif
        <div class="center small" style="margin-top: 1mm;">Atendido por {{ $user }} · Gracias por su preferencia</div>
    </div>
@endsection
