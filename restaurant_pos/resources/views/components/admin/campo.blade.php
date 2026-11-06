{{--
    Campo de formulario genérico.
    tipo: text, number, decimal, email, password, color, date, time, textarea, select, checkbox
--}}
@props(['nombre', 'etiqueta' => null, 'tipo' => 'text', 'valor' => null, 'opciones' => [], 'ayuda' => null, 'requerido' => false, 'vacio' => null])

@php
    $id = 'campo_'.str_replace(['[', ']', '.'], '_', $nombre);
    $clave = str_replace(['[', ']'], ['.', ''], $nombre);
    $actual = old($clave, $valor);
@endphp

<div {{ $attributes->class(['min-w-0']) }}>
    @if ($tipo === 'checkbox')
        <input type="hidden" name="{{ $nombre }}" value="0">
        <label class="flex cursor-pointer items-center gap-2 pt-6 text-sm font-semibold" for="{{ $id }}">
            <input type="checkbox" id="{{ $id }}" name="{{ $nombre }}" value="1" @checked($actual) class="h-4 w-4 rounded border-borde accent-marca-500">
            {{ $etiqueta }}
        </label>
    @else
        @if ($etiqueta)
            <label class="etiqueta" for="{{ $id }}">{{ $etiqueta }} @if ($requerido)<span class="text-marca-600">*</span>@endif</label>
        @endif

        @switch($tipo)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $nombre }}" rows="3" class="campo @error($clave) border-red-400 @enderror">{{ $actual }}</textarea>
                @break
            @case('select')
                <select id="{{ $id }}" name="{{ $nombre }}" class="campo @error($clave) border-red-400 @enderror">
                    @if ($vacio !== null)<option value="">{{ $vacio }}</option>@endif
                    @foreach ($opciones as $v => $texto)
                        <option value="{{ $v }}" @selected((string) $actual === (string) $v)>{{ $texto }}</option>
                    @endforeach
                </select>
                @break
            @case('color')
                <div class="flex gap-2">
                    <input type="color" value="{{ $actual ?: '#337ab7' }}" class="h-9 w-12 cursor-pointer rounded border border-borde" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" id="{{ $id }}" name="{{ $nombre }}" value="{{ $actual }}" class="campo" placeholder="#337ab7">
                </div>
                @break
            @default
                <input type="{{ $tipo === 'decimal' ? 'number' : $tipo }}" id="{{ $id }}" name="{{ $nombre }}"
                       value="{{ $tipo === 'password' ? '' : $actual }}"
                       @if ($tipo === 'decimal') step="0.01" @endif
                       @if ($tipo === 'number') step="1" @endif
                       class="campo @error($clave) border-red-400 @enderror">
        @endswitch

        @if ($ayuda)<p class="mt-1 text-xs text-tenue">{{ $ayuda }}</p>@endif
    @endif
</div>
