@extends('admin.layout')

@section('titulo', 'Facturación · '.$sucursal->nombre)

@section('contenido')
<form method="POST" action="{{ route('admin.facturacion.update', $sucursal) }}" class="max-w-5xl space-y-5">
    @csrf @method('PUT')

    <section class="tarjeta p-6">
        <h2 class="mb-4 font-bold">Datos que se imprimen en la factura</h2>
        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.campo nombre="nomemp" etiqueta="Razón social" :valor="$dosificacion->nomemp" requerido class="sm:col-span-2" />
            <x-admin.campo nombre="nitruc" etiqueta="NIT" :valor="$dosificacion->nitruc" requerido />
            <x-admin.campo nombre="nompro" etiqueta="Propietario" :valor="$dosificacion->nompro" />
            <x-admin.campo nombre="sucursal" etiqueta="Sucursal (texto en factura)" :valor="$dosificacion->sucursal" />
            <x-admin.campo nombre="telemp" etiqueta="Teléfono" :valor="$dosificacion->telemp" />
            <x-admin.campo nombre="ciupai" etiqueta="Ciudad - País" :valor="$dosificacion->ciupai" class="sm:col-span-2" />
            <x-admin.campo nombre="diremp" etiqueta="Dirección" :valor="$dosificacion->diremp" class="sm:col-span-4" />
            <x-admin.campo nombre="leyenda1" etiqueta="Leyenda 1" tipo="textarea" :valor="$dosificacion->leyenda1" class="sm:col-span-2" />
            <x-admin.campo nombre="leyenda2" etiqueta="Leyenda 2" tipo="textarea" :valor="$dosificacion->leyenda2" class="sm:col-span-2" />
        </div>
    </section>

    <section class="tarjeta p-6">
        <h2 class="font-bold">Dosificación</h2>
        <p class="mb-4 text-sm text-tenue">Datos de la resolución de Impuestos Nacionales. Al recibir una nueva dosificación marque «Registrar como nueva» para conservar la anterior en el historial.</p>
        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.campo nombre="nroautori" etiqueta="Nro. de autorización" :valor="$dosificacion->nroautori" requerido />
            <x-admin.campo nombre="fec_docis" etiqueta="Fecha de dosificación" tipo="date" :valor="$dosificacion->fec_docis?->format('Y-m-d')" />
            <x-admin.campo nombre="fecha_lim" etiqueta="Fecha límite de emisión" tipo="date" :valor="$dosificacion->fecha_lim?->format('Y-m-d')" requerido />
            <x-admin.campo nombre="inifac" etiqueta="Factura inicial" tipo="number" :valor="$dosificacion->inifac" />
            <x-admin.campo nombre="llave" etiqueta="Llave de dosificación" :valor="$dosificacion->llave" requerido class="sm:col-span-3" />
            @if ($dosificacion->exists)
                <x-admin.campo nombre="nueva" etiqueta="Registrar como nueva dosificación" tipo="checkbox" :valor="false" />
            @endif
        </div>
    </section>

    <div class="grid gap-5 lg:grid-cols-2">
        <section class="tarjeta p-6">
            <h2 class="font-bold">Numeración</h2>
            <p class="mb-4 text-sm text-tenue">Último número emitido. El POS continúa desde el siguiente.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.campo nombre="folio" etiqueta="Última factura" tipo="number" :valor="$correlativo->folio ?? 0" requerido />
                <x-admin.campo nombre="folior" etiqueta="Último recibo" tipo="number" :valor="$correlativo->folior ?? 0" requerido />
                <x-admin.campo nombre="mult" etiqueta="Factura cada N recibos" tipo="number" :valor="$correlativo->mult" ayuda="Vacío = no aplica." />
            </div>
        </section>
        <section class="tarjeta p-6">
            <h2 class="font-bold">Turnos</h2>
            <p class="mb-4 text-sm text-tenue">Fuera de este horario el POS registra el turno NOCHE; antes de la hora de inicio la venta pertenece al día anterior.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.campo nombre="hinitm" etiqueta="Inicio turno mañana" tipo="time" :valor="substr((string) $dosificacion->hinitm, 0, 5)" requerido />
                <x-admin.campo nombre="hfintm" etiqueta="Fin turno mañana" tipo="time" :valor="substr((string) $dosificacion->hfintm, 0, 5)" requerido />
            </div>
        </section>
    </div>

    <div class="flex gap-2">
        <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
        <a href="{{ route('admin.facturacion.index') }}" class="btn-secundario">Cancelar</a>
    </div>

    @if ($historial->count() > 1)
        <section class="tarjeta overflow-x-auto">
            <h2 class="px-6 pt-5 pb-3 font-bold">Historial de dosificaciones</h2>
            <table class="tabla">
                <thead><tr><th>Autorización</th><th>Fecha dosificación</th><th>Fecha límite</th><th class="text-right">Última factura</th></tr></thead>
                <tbody>
                    @foreach ($historial as $h)
                        <tr>
                            <td class="tabular-nums">{{ $h->nroautori }} @if ($loop->first)<span class="insignia bg-green-100 text-green-800">actual</span>@endif</td>
                            <td>{{ $h->fec_docis?->format('d/m/Y') ?? '—' }}</td>
                            <td>{{ $h->fecha_lim?->format('d/m/Y') }}</td>
                            <td class="num">{{ $h->facact }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</form>
@endsection
