@extends('admin.layout')

@section('title', 'Facturación SIN')

@section('content')
    <p class="mb-4 max-w-3xl text-sm text-muted">Cada sucursal factura con su propia dosificación (número de autorización y llave otorgados por Impuestos Nacionales). Sin dosificación vigente el POS no permite vender.</p>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Sucursal</th><th>NIT</th><th>Autorización</th><th>Fecha límite</th><th class="text-right">Última factura</th><th class="text-right">Último recibo</th><th class="w-1"></th></tr>
            </thead>
            <tbody>
                @foreach ($branches as $row)
                    <tr>
                        <td class="font-semibold">{{ $row['branch']->name }}</td>
                        <td>{{ $row['authorization']?->tax_id ?? '—' }}</td>
                        <td class="tabular-nums">{{ $row['authorization']?->authorization_number ?? '—' }}</td>
                        <td>
                            @if (! $row['authorization'])
                                <span class="badge bg-red-100 text-red-700"><i class="fa fa-exclamation-triangle"></i> Sin dosificación</span>
                            @elseif (! $row['authorization']->isActive())
                                <span class="badge bg-slate-100 text-muted"><i class="fa fa-pause-circle"></i> Desactivada · nota de venta</span>
                            @else
                                {{ $row['authorization']->limit_date?->format('d/m/Y') }}
                                @if ($row['days'] < 0)
                                    <span class="badge bg-red-100 text-red-700"><i class="fa fa-times-circle"></i> Vencida</span>
                                @elseif ($row['days'] <= config('pos.days_warning_authorization'))
                                    <span class="badge bg-amber-100 text-amber-800"><i class="fa fa-clock-o"></i> Vence en {{ $row['days'] }} día(s)</span>
                                @else
                                    <span class="badge bg-green-100 text-green-800"><i class="fa fa-check-circle"></i> Vigente</span>
                                @endif
                            @endif
                        </td>
                        <td class="num">{{ $row['sequence']?->folio ?? '—' }}</td>
                        <td class="num">{{ $row['sequence']?->real_folio ?? '—' }}</td>
                        <td class="text-right"><a href="{{ route('admin.billing.edit', $row['branch']) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Configurar</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
