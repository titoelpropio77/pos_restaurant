{{--
    Campo de formulario genérico.
    tipo: text, number, decimal, email, password, color, date, time, textarea, select, checkbox
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'options' => [], 'help' => null, 'required' => false, 'empty' => null])

@php
    $id = 'field'.str_replace(['[', ']', '.'], '_', $name);
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($key, $value);
@endphp

<div {{ $attributes->class(['min-w-0']) }}>
    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $name }}" value="0">
        <label class="flex cursor-pointer items-center gap-2 pt-6 text-sm font-semibold" for="{{ $id }}">
            <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="1" @checked($current) class="h-4 w-4 rounded border-line accent-brand-500">
            {{ $label }}
        </label>
    @else
        @if ($label)
            <label class="label" for="{{ $id }}">{{ $label }} @if ($required)<span class="text-brand-600">*</span>@endif</label>
        @endif

        @switch($type)
            @case('textarea')
                <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="field @error($key) border-red-400 @enderror">{{ $current }}</textarea>
                @break
            @case('select')
                <select id="{{ $id }}" name="{{ $name }}" class="field @error($key) border-red-400 @enderror">
                    @if ($empty !== null)<option value="">{{ $empty }}</option>@endif
                    @foreach ($options as $v => $text)
                        <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $text }}</option>
                    @endforeach
                </select>
                @break
            @case('color')
                <div class="flex gap-2">
                    <input type="color" value="{{ $current ?: '#337ab7' }}" class="h-9 w-12 cursor-pointer rounded border border-line" oninput="this.nextElementSibling.value = this.value">
                    <input type="text" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}" class="field" placeholder="#337ab7">
                </div>
                @break
            @default
                <input type="{{ $type === 'decimal' ? 'number' : $type }}" id="{{ $id }}" name="{{ $name }}"
                       value="{{ $type === 'password' ? '' : $current }}"
                       @if ($type === 'decimal') step="0.01" @endif
                       @if ($type === 'number') step="1" @endif
                       class="field @error($key) border-red-400 @enderror">
        @endswitch

        @if ($help)<p class="mt-1 text-xs text-muted">{{ $help }}</p>@endif
    @endif
</div>
