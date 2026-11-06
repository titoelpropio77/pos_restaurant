{{--
    Selección múltiple con buscador.
    options: lista de ['id' => ..., 'label' => ...]; value: ids seleccionados.
--}}
@props(['name', 'label' => null, 'options' => [], 'value' => [], 'help' => null, 'required' => false, 'placeholder' => 'Buscar...'])

@php
    $selected = array_map('intval', (array) old($name, $value));
@endphp

<div {{ $attributes->class(['min-w-0']) }}
     x-data="{
         open: false,
         search: '',
         options: @js($options),
         selected: @js($selected),
         get available() {
             const text = this.search.toLowerCase();
             return this.options.filter(o => ! this.selected.includes(o.id) && o.label.toLowerCase().includes(text));
         },
         label(id) { return this.options.find(o => o.id === id)?.label ?? id; },
         add(id) { this.selected.push(id); this.search = ''; this.$refs.search.focus(); },
         remove(id) { this.selected = this.selected.filter(s => s !== id); },
     }"
     @click.outside="open = false"
     @keydown.escape="open = false">
    @if ($label)
        <label class="label">{{ $label }} @if ($required)<span class="text-brand-600">*</span>@endif</label>
    @endif

    <div class="relative">
        <div class="field flex min-h-[38px] flex-wrap items-center gap-1 @error($name) border-red-400 @enderror" @click="open = true; $refs.search.focus()">
            <template x-for="id in selected" :key="id">
                <span class="badge bg-brand-100 text-brand-700">
                    <span x-text="label(id)"></span>
                    <button type="button" class="cursor-pointer" title="Quitar" @click.stop="remove(id)"><i class="fa fa-times"></i></button>
                    <input type="hidden" name="{{ $name }}[]" :value="id">
                </span>
            </template>
            <input x-ref="search" x-model="search" type="text" class="min-w-24 flex-1 bg-transparent text-sm outline-none" placeholder="{{ $placeholder }}"
                   @focus="open = true" @keydown.enter.prevent="available[0] && add(available[0].id)">
        </div>

        <ul x-show="open" x-cloak class="absolute z-30 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-line bg-white py-1 text-sm shadow-lg">
            <template x-for="option in available" :key="option.id">
                <li><button type="button" class="w-full cursor-pointer px-3 py-2 text-left hover:bg-brand-50" x-text="option.label" @click="add(option.id)"></button></li>
            </template>
            <li x-show="available.length === 0" class="px-3 py-2 text-muted">Sin resultados</li>
        </ul>
    </div>

    @if ($help)<p class="mt-1 text-xs text-muted">{{ $help }}</p>@endif
</div>
