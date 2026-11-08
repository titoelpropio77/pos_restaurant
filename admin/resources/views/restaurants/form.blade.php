@extends('layout')

@section('title', $restaurant->exists ? 'Editar restaurante' : 'Nuevo restaurante')

@section('content')
    <form method="POST" action="{{ $restaurant->exists ? route('restaurants.update', $restaurant) : route('restaurants.store') }}" enctype="multipart/form-data" class="max-w-4xl space-y-4">
        @csrf
        @if ($restaurant->exists) @method('PUT') @endif

        <section class="card p-6">
            <h2 class="mb-4 font-bold">Datos del restaurante</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="name" label="Nombre" :value="$restaurant->name" required class="sm:col-span-2" />
                <x-field name="tax_id" label="NIT" :value="$restaurant->tax_id" />
                <x-multiselect name="partners" label="Propietarios" :options="$partners" :value="$restaurant->exists ? $restaurant->partners->pluck('id')->all() : []" :required="$restaurant->exists" placeholder="Buscar partner..." class="sm:col-span-3" />
                <x-field name="email" label="Correo" type="email" :value="$restaurant->email" />
                <x-field name="phone" label="Teléfono" :value="$restaurant->phone" />
                <x-field name="status" label="Estado" type="select" :options="['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo']" :value="$restaurant->status" />
                <x-field name="address" label="Dirección" :value="$restaurant->address" class="sm:col-span-3" />
            </div>
        </section>

        {{-- Logo y fondo de la tienda: la vista previa muestra el logo elegido sobre el fondo elegido;
             sin archivo nuevo se conserva el logo guardado --}}
        <section class="card p-6" x-data="{
            preview: @js($restaurant->logo),
            removed: false,
            background: @js(old('background_color', $restaurant->background_color) ?? ''),
            get validBackground() { return /^#[0-9a-f]{6}$/i.test(this.background) },
        }">
            <h2 class="mb-4 font-bold">Apariencia de la tienda</h2>
            <div class="flex flex-wrap items-start gap-5">
                <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-line bg-white"
                     :style="validBackground && { background: background }">
                    <img x-show="preview" x-cloak :src="preview" class="max-h-full max-w-full object-contain p-2" alt="Vista previa del logo">
                    <span x-show="!preview" class="px-2 text-center text-xs text-muted"><i class="fa fa-image mb-1 block text-2xl"></i>Sin logo</span>
                </div>

                <div class="min-w-0 flex-1 space-y-5">
                    <div class="space-y-2">
                        <label class="label" for="logo">Logo</label>
                        <input type="file" name="logo" id="logo" x-ref="file" accept="{{ collect(\App\Models\Restaurant::LOGO_MIMES)->map(fn ($e) => '.'.$e)->join(',') }}"
                               @change="const file = $event.target.files[0]; if (file) { preview = URL.createObjectURL(file); removed = false }"
                               class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold @error('logo') text-red-600 @enderror">
                        <p class="text-xs text-muted">PNG, JPG o WebP de hasta {{ \App\Models\Restaurant::LOGO_MAX_KB / 1024 }} MB. Se recomienda fondo transparente.</p>
                        <input type="hidden" name="remove_logo" :value="removed ? 1 : 0">
                        <button type="button" x-show="preview" x-cloak class="btn-secondary btn-small"
                                @click="preview = null; removed = true; $refs.file.value = ''">
                            <i class="fa fa-trash"></i> Quitar logo
                        </button>
                    </div>

                    <div>
                        <span class="label">Fondo de la tienda</span>
                        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Fondo de la tienda">
                            @foreach (\App\Models\Restaurant::BACKGROUND_PALETTE as $hex => $colorName)
                                <button type="button" role="radio" title="{{ $colorName }}" aria-label="{{ $colorName }}"
                                        :aria-checked="background.toUpperCase() === '{{ $hex }}'"
                                        @click="background = '{{ $hex }}'"
                                        class="h-9 w-9 rounded-full border border-line shadow-sm transition hover:scale-110"
                                        :class="background.toUpperCase() === '{{ $hex }}' && 'ring-2 ring-brand-500 ring-offset-2'"
                                        style="background: {{ $hex }}"></button>
                            @endforeach
                        </div>
                        <div class="mt-3 flex max-w-xs gap-2">
                            <input type="color" :value="validBackground ? background : '#FAF7F2'" @input="background = $event.target.value.toUpperCase()"
                                   class="h-9 w-12 shrink-0 cursor-pointer rounded border border-line" title="Otro color">
                            <input type="text" name="background_color" id="background_color" x-model="background" placeholder="#FAF7F2" maxlength="7"
                                   class="field @error('background_color') border-red-400 @enderror">
                            <button type="button" x-show="background" x-cloak @click="background = ''" class="btn-secondary btn-small shrink-0" title="Sin fondo">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @unless ($restaurant->exists)
            <section class="card p-6">
                <h2 class="font-bold">Casa matriz</h2>
                <p class="mb-4 text-sm text-muted">Sucursal principal: se crea con su almacén y su numeración de ventas.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field name="main_name" label="Nombre" value="CASA MATRIZ" required />
                    <x-field name="main_city_id" label="Ciudad" type="select" :options="$cities" empty="—" />
                    <x-field name="main_phone" label="Teléfono" />
                    <x-field name="main_address" label="Dirección" class="sm:col-span-3" />
                </div>
            </section>

            <section class="card p-6">
                <h2 class="font-bold">Partner nuevo</h2>
                <p class="mb-4 text-sm text-muted">Complete esto sólo si el dueño aún no tiene cuenta; si ya la tiene, elíjalo en Propietarios. El correo no distingue mayúsculas; la contraseña sí.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="owner_names" label="Nombre completo" />
                    <x-field name="owner_email" label="Correo" type="email" help="Con este correo ingresa al sistema." />
                    <x-field name="owner_user" label="Código" help="Nombre corto que aparece en ventas y reportes, sin espacios." />
                    <x-field name="owner_password" label="Contraseña" type="password" help="Mínimo 4 caracteres." />
                    <x-field name="owner_password_confirmation" label="Repetir contraseña" type="password" />
                </div>
            </section>
        @endunless

        <section class="card p-6">
            <h2 class="mb-2 font-bold">Opciones del POS</h2>
            <div class="grid gap-x-4 sm:grid-cols-2">
                @foreach (\App\Models\Restaurant::OPTIONS as $key => [$text, $initial])
                    {{-- Una opción que el restaurante aún no guardó muestra su valor inicial --}}
                    <x-field :name="'options['.$key.']'" :label="$text" type="checkbox" :value="$restaurant->options[$key] ?? $initial" />
                @endforeach
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ $restaurant->exists ? route('restaurants.show', $restaurant) : route('restaurants.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
