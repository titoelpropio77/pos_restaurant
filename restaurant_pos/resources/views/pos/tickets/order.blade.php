{{-- Comanda: una hoja por estación, sin precios. --}}
@extends('pos.tickets.layout')

@section('title', 'Comanda')
@section('empty', empty($stations) ? '1' : '0')

@section('content')
    @foreach ($stations as $station => $items)
        <div class="ticket">
            <div class="center bold inverse">{{ $station }}</div>
            @if ($header['additional'])
                <div class="center bold" style="margin-top: 1mm;">*** ADICIONAL ***</div>
            @endif
            <div class="center huge" style="margin-top: 1mm;">{{ $header['title'] }}</div>
            @if ($header['subtitle'])
                <div class="center big">{{ $header['subtitle'] }}</div>
            @endif
            <hr class="rule">
            @if ($header['number'])<div>{{ $header['number'] }}</div>@endif
            @if ($header['waiter'])<div>Mesero: <span class="bold">{{ $header['waiter'] }}</span></div>@endif
            <div class="small">{{ \Illuminate\Support\Carbon::parse($header['date'])->format('d/m/Y') }} {{ $header['time'] }}@if ($header['user']) · {{ $header['user'] }}@endif</div>
            <hr class="rule-solid">
            <table>
                @foreach ($items as $item)
                    <tr class="big">
                        <td class="qty">{{ rtrim(rtrim(number_format($item['quantity'], 2, '.', ''), '0'), '.') }}</td>
                        <td>{{ $item['name'] }}</td>
                    </tr>
                    @if (filled($item['note']))
                        <tr><td></td><td class="note">→ {{ mb_strtoupper($item['note']) }}</td></tr>
                    @endif
                @endforeach
            </table>
            <hr class="rule-solid">
            <div class="center small">Impreso {{ $printedAt->format('H:i') }}</div>
        </div>
    @endforeach
@endsection
