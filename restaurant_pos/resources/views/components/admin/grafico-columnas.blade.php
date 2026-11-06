{{--
    Gráfico de columnas de una sola serie (HTML/CSS, sin librerías).
    Columnas de hasta 24px con extremo redondeado, eje Y con valores redondos,
    tooltip al pasar el cursor o enfocar con teclado, y tabla equivalente accesible.
    serie: [['etiqueta' => 'd/m', 'detalle' => 'texto largo', 'valor' => float], ...]
--}}
@props(['serie', 'titulo', 'prefijo' => 'Bs '])

@php
    $maximo = max(1, collect($serie)->max('valor'));
    $paso = 10 ** floor(log10($maximo / 4));
    $paso = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $paso)->first(fn ($p) => $p * 4 >= $maximo);
    $tope = $paso * 4;
    $marcas = collect(range(4, 0))->map(fn ($i) => $i * $paso);
    $etiquetasCada = max(1, (int) ceil(count($serie) / 12));
@endphp

<figure {{ $attributes->class(['tarjeta p-5']) }} x-data="{ tip: null }">
    <figcaption class="mb-4 flex items-baseline justify-between gap-4">
        <h2 class="font-bold">{{ $titulo }}</h2>
        <span class="text-sm text-tenue">Total: <strong class="text-tinta">{{ $prefijo }}{{ number_format(collect($serie)->sum('valor'), 2) }}</strong></span>
    </figcaption>

    <div class="relative flex h-64 gap-2">
        {{-- Eje Y --}}
        <div class="flex w-14 flex-col justify-between pb-6 text-right text-[11px] text-tenue tabular-nums">
            @foreach ($marcas as $marca)
                <span class="-translate-y-1/2 leading-none">{{ number_format($marca) }}</span>
            @endforeach
        </div>

        <div class="relative flex-1">
            {{-- Líneas guía --}}
            <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between">
                @foreach ($marcas as $marca)
                    <div class="border-t border-slate-200"></div>
                @endforeach
            </div>

            {{-- Columnas --}}
            <div class="absolute inset-x-0 top-0 bottom-6 flex items-end">
                @foreach ($serie as $i => $punto)
                    <div class="group relative flex h-full flex-1 cursor-default items-end justify-center outline-none"
                         tabindex="0"
                         @mouseenter="tip = {{ $i }}" @mouseleave="tip = null" @focus="tip = {{ $i }}" @blur="tip = null">
                        <div class="w-full max-w-6 rounded-t bg-marca-500 transition group-hover:bg-marca-600 group-focus:bg-marca-600"
                             style="height: {{ $punto['valor'] > 0 ? max(0.6, $punto['valor'] / $tope * 100) : 0 }}%"></div>
                        <div x-show="tip === {{ $i }}" x-cloak
                             class="pointer-events-none absolute bottom-full z-10 mb-2 whitespace-nowrap rounded-lg bg-panel px-3 py-2 text-xs text-white shadow-lg"
                             style="bottom: calc({{ min(100, $punto['valor'] / $tope * 100) }}% + 8px)">
                            <strong class="block text-sm">{{ $prefijo }}{{ number_format($punto['valor'], 2) }}</strong>
                            <span class="text-slate-300">{{ $punto['detalle'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Eje X --}}
            <div class="absolute inset-x-0 bottom-0 flex h-5 border-t border-slate-300">
                @foreach ($serie as $i => $punto)
                    <span class="flex-1 pt-1 text-center text-[11px] text-tenue">{{ $i % $etiquetasCada === 0 ? $punto['etiqueta'] : '' }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-tenue">Ver como tabla</summary>
        <table class="tabla mt-2">
            <thead><tr><th>Período</th><th class="text-right">Ventas</th></tr></thead>
            <tbody>
                @foreach ($serie as $punto)
                    <tr><td>{{ $punto['detalle'] }}</td><td class="num">{{ $prefijo }}{{ number_format($punto['valor'], 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
