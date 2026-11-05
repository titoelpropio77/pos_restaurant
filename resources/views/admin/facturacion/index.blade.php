@extends('admin.layout')

@section('titulo', 'Facturación SIN')

@section('contenido')
    <p class="mb-4 max-w-3xl text-sm text-tenue">Cada sucursal factura con su propia dosificación (número de autorización y llave otorgados por Impuestos Nacionales). Sin dosificación vigente el POS no permite vender.</p>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr><th>Sucursal</th><th>NIT</th><th>Autorización</th><th>Fecha límite</th><th class="text-right">Última factura</th><th class="text-right">Último recibo</th><th class="w-1"></th></tr>
            </thead>
            <tbody>
                @foreach ($sucursales as $fila)
                    <tr>
                        <td class="font-semibold">{{ $fila['sucursal']->nombre }}</td>
                        <td>{{ $fila['dosificacion']?->nitruc ?? '—' }}</td>
                        <td class="tabular-nums">{{ $fila['dosificacion']?->nroautori ?? '—' }}</td>
                        <td>
                            @if (! $fila['dosificacion'])
                                <span class="insignia bg-red-100 text-red-700"><i class="fa fa-exclamation-triangle"></i> Sin dosificación</span>
                            @else
                                {{ $fila['dosificacion']->fecha_lim?->format('d/m/Y') }}
                                @if ($fila['dias'] < 0)
                                    <span class="insignia bg-red-100 text-red-700"><i class="fa fa-times-circle"></i> Vencida</span>
                                @elseif ($fila['dias'] <= config('posgourmet.dias_aviso_dosificacion'))
                                    <span class="insignia bg-amber-100 text-amber-800"><i class="fa fa-clock-o"></i> Vence en {{ $fila['dias'] }} día(s)</span>
                                @else
                                    <span class="insignia bg-green-100 text-green-800"><i class="fa fa-check-circle"></i> Vigente</span>
                                @endif
                            @endif
                        </td>
                        <td class="num">{{ $fila['correlativo']?->folio ?? '—' }}</td>
                        <td class="num">{{ $fila['correlativo']?->folior ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('admin.facturacion.edit', $fila['sucursal']) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Configurar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
