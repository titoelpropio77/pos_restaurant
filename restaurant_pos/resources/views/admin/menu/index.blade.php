@extends('admin.layout')

@section('title', 'Menú QR')

@section('content')
    <p class="mb-5 max-w-3xl text-sm text-muted">
        Cada sucursal tiene su menú digital con sus propios precios y productos. Los clientes escanean el QR con la cámara del celular
        y ven la carta con fotos, ordenada por categorías y con los colores y el logo de la tienda. El menú se actualiza solo
        cuando cambias productos, precios o fotos: no hace falta volver a imprimir el QR.
    </p>

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($branches as $row)
            <section class="card flex flex-col gap-5 p-6 sm:flex-row" x-data="{ copied: false }">
                <div class="mx-auto w-44 shrink-0 rounded-xl border border-line bg-white p-3 sm:mx-0 [&>svg]:h-auto [&>svg]:w-full">
                    {!! $row['qr'] !!}
                </div>
                <div class="flex min-w-0 flex-1 flex-col gap-3">
                    <div>
                        <h2 class="font-bold">{{ $row['branch']->name }}</h2>
                        @if ($row['branch']->address)
                            <p class="text-sm text-muted">{{ $row['branch']->address }}</p>
                        @endif
                    </div>

                    <div>
                        <span class="label">Link del menú</span>
                        <div class="flex gap-2">
                            <input type="text" readonly value="{{ $row['url'] }}" x-ref="link" class="field min-w-0 text-xs" @focus="$el.select()" aria-label="Link del menú de {{ $row['branch']->name }}">
                            {{-- navigator.clipboard sólo existe en https o localhost: en la red local se copia seleccionando el texto --}}
                            <button type="button" class="btn-secondary btn-small shrink-0"
                                    @click="(navigator.clipboard ? navigator.clipboard.writeText($refs.link.value) : Promise.reject())
                                        .catch(() => { $refs.link.select(); document.execCommand('copy') })
                                        .finally(() => { copied = true; setTimeout(() => copied = false, 1800) })">
                                <i class="fa" :class="copied ? 'fa-check' : 'fa-clone'"></i>
                                <span x-text="copied ? 'Copiado' : 'Copiar'">Copiar</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-auto flex flex-wrap gap-2">
                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="btn-primary btn-small"><i class="fa fa-external-link"></i> Ver menú</a>
                        <a href="{{ route('admin.menu.poster', $row['branch']) }}" target="_blank" class="btn-secondary btn-small"><i class="fa fa-print"></i> Afiche para mesas</a>
                        <a href="{{ route('admin.menu.qr', $row['branch']) }}" class="btn-secondary btn-small"><i class="fa fa-download"></i> Descargar QR</a>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <div class="mt-6 max-w-3xl space-y-1 text-sm text-muted">
        <p><i class="fa fa-lightbulb-o"></i> Para que el menú luzca: sube una foto a cada producto y escribe una descripción corta en <a href="{{ route('admin.products.index') }}" class="font-semibold underline">Productos</a>.</p>
        <p><i class="fa fa-paint-brush"></i> El logo y el fondo de la tienda se configuran en la ficha del restaurante del sistema administrador.</p>
        <p><i class="fa fa-clock-o"></i> Los productos con días u horario de venta aparecen con su horario; fuera de él se marcan como no disponibles.</p>
    </div>
@endsection
