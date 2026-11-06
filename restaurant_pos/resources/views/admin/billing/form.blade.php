@extends('admin.layout')

@section('title', 'Facturación · '.$branch->name)

@section('content')
<form method="POST" action="{{ route('admin.billing.update', $branch) }}" class="max-w-5xl space-y-5">
    @csrf @method('PUT')

    <section class="card p-6">
        <h2 class="mb-4 font-bold">Datos que se imprimen en la factura</h2>
        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.field name="business_name" label="Razón social" :value="$authorization->business_name" required class="sm:col-span-2" />
            <x-admin.field name="tax_id" label="NIT" :value="$authorization->tax_id" required />
            <x-admin.field name="owner_name" label="Propietario" :value="$authorization->owner_name" />
            <x-admin.field name="branch" label="Sucursal (texto en factura)" :value="$authorization->branch" />
            <x-admin.field name="business_phone" label="Teléfono" :value="$authorization->business_phone" />
            <x-admin.field name="city_country" label="Ciudad - País" :value="$authorization->city_country" class="sm:col-span-2" />
            <x-admin.field name="business_address" label="Dirección" :value="$authorization->business_address" class="sm:col-span-4" />
            <x-admin.field name="legend_1" label="Leyenda 1" type="textarea" :value="$authorization->legend_1" class="sm:col-span-2" />
            <x-admin.field name="legend_2" label="Leyenda 2" type="textarea" :value="$authorization->legend_2" class="sm:col-span-2" />
        </div>
    </section>

    <section class="card p-6">
        <h2 class="font-bold">Dosificación</h2>
        <p class="mb-4 text-sm text-muted">Datos de la resolución de Impuestos Nacionales. Al recibir una nueva dosificación marque «Registrar como nueva» para conservar la anterior en el historial.</p>
        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.field name="authorization_number" label="Nro. de autorización" :value="$authorization->authorization_number" required />
            <x-admin.field name="authorization_date" label="Fecha de dosificación" type="date" :value="$authorization->authorization_date?->format('Y-m-d')" />
            <x-admin.field name="limit_date" label="Fecha límite de emisión" type="date" :value="$authorization->limit_date?->format('Y-m-d')" required />
            <x-admin.field name="first_invoice_number" label="Factura inicial" type="number" :value="$authorization->first_invoice_number" />
            <x-admin.field name="auth_key" label="Llave de dosificación" :value="$authorization->auth_key" required class="sm:col-span-3" />
            @if ($authorization->exists)
                <x-admin.field name="new" label="Registrar como nueva dosificación" type="checkbox" :value="false" />
            @endif
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="card p-6">
            <h2 class="font-bold">Numeración</h2>
            <p class="mb-4 text-sm text-muted">Último número emitido. El POS continúa desde el siguiente.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.field name="folio" label="Última factura" type="number" :value="$sequence->folio ?? 0" required />
                <x-admin.field name="real_folio" label="Último recibo" type="number" :value="$sequence->real_folio ?? 0" required />
                <x-admin.field name="receipt_multiplier" label="Factura cada N recibos" type="number" :value="$sequence->receipt_multiplier" help="Vacío = no aplica." />
            </div>
        </section>
        <section class="card p-6">
            <h2 class="font-bold">Turnos</h2>
            <p class="mb-4 text-sm text-muted">Fuera de este horario el POS registra el turno NOCHE; antes de la hora de inicio la venta pertenece al día anterior.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.field name="morning_start" label="Inicio turno mañana" type="time" :value="substr((string) $authorization->morning_start, 0, 5)" required />
                <x-admin.field name="morning_end" label="Fin turno mañana" type="time" :value="substr((string) $authorization->morning_end, 0, 5)" required />
            </div>
        </section>
    </div>

    <div class="flex gap-2">
        <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
        <a href="{{ route('admin.billing.index') }}" class="btn-secondary">Cancelar</a>
    </div>

    @if ($history->count() > 1)
        <section class="card overflow-x-auto">
            <h2 class="px-6 pt-5 pb-3 font-bold">Historial de dosificaciones</h2>
            <table class="data-table">
                <thead><tr><th>Autorización</th><th>Fecha dosificación</th><th>Fecha límite</th><th class="text-right">Última factura</th></tr></thead>
                <tbody>
                    @foreach ($history as $h)
                        <tr>
                            <td class="tabular-nums">{{ $h->authorization_number }} @if ($loop->first)<span class="badge bg-green-100 text-green-800">actual</span>@endif</td>
                            <td>{{ $h->authorization_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $h->limit_date?->format('d/m/Y') }}</td>
                            <td class="num">{{ $h->current_invoice }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</form>
@endsection
