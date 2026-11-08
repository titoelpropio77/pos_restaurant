@extends('admin.layout')

@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')

@php use App\Http\Controllers\Admin\ProductController as PC; @endphp

@section('content')
<form method="POST" enctype="multipart/form-data"
      action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      x-data="{
          recipe: @js($recipe),
          groups: @js($groupsProduct),
          supplies: @js($supplies->map(fn ($i) => ['id' => $i->id, 'name' => $i->supply_name, 'measure' => $i->measure])),
          measure(id) { const i = this.supplies.find(x => x.id == id); return i ? i.measure : ''; }
      }">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            {{-- Datos generales --}}
            <section class="card p-6">
                <h2 class="mb-4 font-bold">Datos generales</h2>
                <div class="grid gap-4 sm:grid-cols-4">
                    <x-admin.field name="product_name" label="Nombre" :value="$product->product_name" required class="sm:col-span-2" />
                    <x-admin.field name="category_id" label="Categoría" type="select" :options="$categories" :value="$product->category_id" empty="Seleccione..." required />
                    <x-admin.field name="sale_price" label="Precio de venta (Bs)" type="decimal" :value="$product->sale_price" required />
                    <x-admin.field name="cost_price" label="Precio de costo (Bs)" type="decimal" :value="$product->exists ? $product->cost_price : null" />
                    <x-admin.field name="barcode" label="Código de barras" :value="$product->barcode" />
                    <x-admin.field name="quantity" label="Cantidad por venta" type="decimal" :value="$product->quantity" help="Unidades que agrega cada toque." />
                    <x-admin.field name="status" label="Estado" type="select" :options="['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo']" :value="$product->status" />
                    <x-admin.field name="asks_options" label="Pide opciones al vender" type="checkbox" :value="(bool) $product->options_mode" class="sm:col-span-2" />
                    <x-admin.field name="por_time" label="Se cobra por tiempo (billar)" type="checkbox" :value="$product->timed === 'T'" class="sm:col-span-2" />
                    <x-admin.field name="description" label="Descripción para el menú digital" type="textarea" :value="$product->description"
                                   help="Opcional, hasta 300 caracteres. Ej.: Carne a la plancha con arroz, papa y huevo frito." class="sm:col-span-4" />
                </div>
            </section>

            {{-- Configuración por sucursal --}}
            <section class="card p-6">
                <h2 class="font-bold">Disponibilidad por sucursal</h2>
                <p class="mb-4 text-sm text-muted">Precio, días y horario en que aparece en el POS, e impresoras donde sale la comanda. Precio vacío = precio general. Horario 00:00 a 00:00 = todo el día.</p>
                <div class="space-y-4">
                    @foreach ($branches as $branch)
                        @php
                            $c = $configuration->get($branch->id);
                            $base = "branches[{$branch->id}]";
                            $enabled = old("branches.{$branch->id}.enabled", $c ? true : ! $product->exists);
                            $days = old("branches.{$branch->id}.days", $c ? collect(PC::DAYS)->keys()->filter(fn ($d) => $c->{$d})->values()->all() : array_keys(PC::DAYS));
                            $printers = old("branches.{$branch->id}.printers", $c ? collect(PC::PRINTERS)->keys()->filter(fn ($i) => $c->{$i})->values()->all() : []);
                        @endphp
                        <div class="rounded-lg border border-line p-4" x-data="{ on: @js((bool) $enabled) }">
                            <label class="flex cursor-pointer items-center gap-2 font-semibold">
                                <input type="hidden" name="{{ $base }}[enabled]" value="0">
                                <input type="checkbox" name="{{ $base }}[enabled]" value="1" x-model="on" class="h-4 w-4 accent-brand-500">
                                {{ $branch->name }}
                                <span class="text-xs font-normal text-muted" x-show="!on">— no se vende en esta sucursal</span>
                            </label>
                            <div class="mt-3 grid gap-4 sm:grid-cols-4" x-show="on" x-cloak>
                                <x-admin.field :name="$base.'[sale_price]'" label="Precio (Bs)" type="decimal" :value="$c && (float) $c->sale_price > 0 && (float) $c->sale_price !== (float) $product->sale_price ? $c->sale_price : null" />
                                <x-admin.field :name="$base.'[wholesale_price]'" label="Precio mayorista" type="decimal" :value="$c?->wholesale_price" />
                                <x-admin.field :name="$base.'[start_time]'" label="Desde" type="time" :value="$c?->start_time ?: '00:00'" />
                                <x-admin.field :name="$base.'[end_time]'" label="Hasta" type="time" :value="$c?->end_time ?: '00:00'" />
                                <div class="sm:col-span-2">
                                    <span class="label">Días</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (PC::DAYS as $key => $text)
                                            <label class="cursor-pointer">
                                                <input type="checkbox" name="{{ $base }}[days][]" value="{{ $key }}" @checked(in_array($key, $days)) class="peer sr-only">
                                                <span class="inline-flex h-8 w-9 items-center justify-center rounded-md border border-line text-xs font-semibold text-muted peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-panel">{{ $text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="label">Imprime en</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (PC::PRINTERS as $key => $text)
                                            <label class="cursor-pointer">
                                                <input type="checkbox" name="{{ $base }}[printers][]" value="{{ $key }}" @checked(in_array($key, $printers)) class="peer sr-only">
                                                <span class="inline-flex h-8 items-center rounded-md border border-line px-2.5 text-xs font-semibold text-muted peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-panel">{{ $text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <x-admin.field :name="$base.'[expires_on]'" label="Vence (opcional)" type="date" :value="$c && $c->expires_on && $c->expires_on->year < 2999 ? $c->expires_on->format('Y-m-d') : null" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Receta --}}
            <section class="card p-6">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold">Receta</h2>
                        <p class="text-sm text-muted">Insumos que se descuentan del almacén por cada unidad vendida.</p>
                    </div>
                    <button type="button" class="btn-secondary btn-small" @click="recipe.push({ supply_id: '', quantity: '' })"><i class="fa fa-plus"></i> Agregar insumo</button>
                </div>
                <template x-for="(item, i) in recipe" :key="i">
                    <div class="mb-2 grid grid-cols-[1fr_9rem_auto] gap-2">
                        <select class="field" :name="`recipe[${i}][supply_id]`" x-model="item.supply_id" required>
                            <option value="">Seleccione el insumo...</option>
                            <template x-for="ins in supplies" :key="ins.id">
                                <option :value="ins.id" x-text="ins.name" :selected="ins.id == item.supply_id"></option>
                            </template>
                        </select>
                        <div class="relative">
                            <input type="number" step="0.001" min="0" class="field pr-12" :name="`recipe[${i}][quantity]`" x-model="item.quantity" placeholder="Cantidad" required>
                            <span class="absolute top-2 right-3 text-xs text-muted" x-text="measure(item.supply_id)"></span>
                        </div>
                        <button type="button" class="btn-danger" @click="recipe.splice(i, 1)"><i class="fa fa-trash-o"></i></button>
                    </div>
                </template>
                <p class="text-sm text-muted" x-show="recipe.length === 0">Sin receta: la venta no descuenta inventario.</p>
            </section>
        </div>

        <div class="space-y-5">
            <section class="card p-6">
                <h2 class="mb-4 font-bold">Botón en el POS</h2>
                <div class="mb-4 flex h-24 flex-col justify-between rounded-lg p-3 text-sm font-bold text-white shadow"
                     style="background: {{ old('color', $product->color) ?: '#337ab7' }}" id="viewButton">
                    <span>{{ $product->product_name ?: 'NOMBRE DEL PRODUCTO' }}</span>
                    <span class="self-end rounded-full bg-white/90 px-2 py-0.5 text-xs text-ink">Bs {{ number_format((float) $product->sale_price, 2) }}</span>
                </div>
                <x-admin.field name="color" label="Color del botón" type="color" :value="$product->color" />
                <div class="mt-4">
                    <label class="label" for="image">Imagen (opcional)</label>
                    @if ($product->image_path)
                        <img src="{{ asset($product->image_path) }}" class="mb-2 h-24 rounded-lg object-cover" alt="">
                        <x-admin.field name="remove_image" label="Quitar imagen" type="checkbox" :value="false" class="-mt-4 mb-2" />
                    @endif
                    <input type="file" name="image" id="image" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold">
                </div>
            </section>

            <section class="card p-6">
                <h2 class="font-bold">Opciones al vender</h2>
                <p class="mb-3 text-sm text-muted">Una por línea (p. ej. SIN PICANTE). Se muestran si está marcado «Pide opciones al vender».</p>
                <textarea name="options" rows="5" class="field">{{ old('options', $product->options->pluck('option_name')->implode("\n")) }}</textarea>
            </section>

            <section class="card p-6">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold">Grupos de reporte</h2>
                    <button type="button" class="btn-secondary btn-small" @click="groups.push({ report_group_id: '', factor: 1 })"><i class="fa fa-plus"></i></button>
                </div>
                <template x-for="(item, i) in groups" :key="i">
                    <div class="mb-2 grid grid-cols-[1fr_5rem_auto] gap-2">
                        <select class="field" :name="`report_groups[${i}][report_group_id]`" x-model="item.report_group_id" required>
                            <option value="">Grupo...</option>
                            @foreach ($groups as $id => $name)
                                <option value="{{ $id }}" :selected="item.report_group_id == {{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" class="field" :name="`report_groups[${i}][factor]`" x-model="item.factor" title="Factor" required>
                        <button type="button" class="btn-danger" @click="groups.splice(i, 1)"><i class="fa fa-trash-o"></i></button>
                    </div>
                </template>
                <p class="text-sm text-muted" x-show="groups.length === 0">Opcional. El factor indica cuántas unidades del grupo suma cada venta.</p>
            </section>
        </div>
    </div>

    <div class="sticky bottom-0 mt-5 flex gap-2 border-t border-line bg-base/95 py-4 backdrop-blur">
        <button class="btn-primary"><i class="fa fa-check"></i> Guardar producto</button>
        <a href="{{ route('admin.products.index') }}" class="btn-secondary">Volver</a>
    </div>
</form>
@endsection
