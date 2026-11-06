{{--
    Gráfico de columnas de una sola serie (HTML/CSS, sin librerías).
    Columnas de hasta 24px con extremo redondeado, eje Y con valores redondos,
    tooltip al pasar el cursor o enfocar con teclado, y tabla equivalente accesible.
    serie: [['etiqueta' => 'd/m', 'detalle' => 'texto largo', 'valor' => float], ...]
--}}
@props(['series', 'title', 'prefix' => 'Bs '])

@php
    $maximo = max(1, collect($series)->max('value'));
    $step = 10 ** floor(log10($maximo / 4));
    $step = collect([1, 2, 2.5, 5, 10])->map(fn ($m) => $m * $step)->first(fn ($p) => $p * 4 >= $maximo);
    $top = $step * 4;
    $marks = collect(range(4, 0))->map(fn ($i) => $i * $step);
    $labelsEach = max(1, (int) ceil(count($series) / 12));
@endphp

<figure {{ $attributes->class(['card p-5']) }} x-data="{ tip: null }">
    <figcaption class="mb-4 flex items-baseline justify-between gap-4">
        <h2 class="font-bold">{{ $title }}</h2>
        <span class="text-sm text-muted">Total: <strong class="text-ink">{{ $prefix }}{{ number_format(collect($series)->sum('value'), 2) }}</strong></span>
    </figcaption>

    <div class="relative flex h-64 gap-2">
        {{-- Eje Y --}}
        <div class="flex w-14 flex-col justify-between pb-6 text-right text-[11px] text-muted tabular-nums">
            @foreach ($marks as $brand)
                <span class="-translate-y-1/2 leading-none">{{ number_format($brand) }}</span>
            @endforeach
        </div>

        <div class="relative flex-1">
            {{-- Líneas guía --}}
            <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between">
                @foreach ($marks as $brand)
                    <div class="border-t border-slate-200"></div>
                @endforeach
            </div>

            {{-- Columnas --}}
            <div class="absolute inset-x-0 top-0 bottom-6 flex items-end">
                @foreach ($series as $i => $point)
                    <div class="group relative flex h-full flex-1 cursor-default items-end justify-center outline-none"
                         tabindex="0"
                         @mouseenter="tip = {{ $i }}" @mouseleave="tip = null" @focus="tip = {{ $i }}" @blur="tip = null">
                        <div class="w-full max-w-6 rounded-t bg-brand-500 transition group-hover:bg-brand-600 group-focus:bg-brand-600"
                             style="height: {{ $point['value'] > 0 ? max(0.6, $point['value'] / $top * 100) : 0 }}%"></div>
                        <div x-show="tip === {{ $i }}" x-cloak
                             class="pointer-events-none absolute bottom-full z-10 mb-2 whitespace-nowrap rounded-lg bg-panel px-3 py-2 text-xs text-white shadow-lg"
                             style="bottom: calc({{ min(100, $point['value'] / $top * 100) }}% + 8px)">
                            <strong class="block text-sm">{{ $prefix }}{{ number_format($point['value'], 2) }}</strong>
                            <span class="text-slate-300">{{ $point['detail'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Eje X --}}
            <div class="absolute inset-x-0 bottom-0 flex h-5 border-t border-slate-300">
                @foreach ($series as $i => $point)
                    <span class="flex-1 pt-1 text-center text-[11px] text-muted">{{ $i % $labelsEach === 0 ? $point['label'] : '' }}</span>
                @endforeach
            </div>
        </div>
    </div>

    <details class="mt-3 text-sm">
        <summary class="cursor-pointer text-muted">Ver como tabla</summary>
        <table class="data-table mt-2">
            <thead><tr><th>Período</th><th class="text-right">Ventas</th></tr></thead>
            <tbody>
                @foreach ($series as $point)
                    <tr><td>{{ $point['detail'] }}</td><td class="num">{{ $prefix }}{{ number_format($point['value'], 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</figure>
