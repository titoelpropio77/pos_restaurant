@extends('admin.layout')

@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')

@php use App\Http\Controllers\Admin\ProductoController as PC; @endphp

@section('contenido')
<form method="POST" enctype="multipart/form-data"
      action="{{ $producto->exists ? route('admin.productos.update', $producto) : route('admin.productos.store') }}"
      x-data="{
          receta: @js($receta),
          grupos: @js($gruposProducto),
          insumos: @js($insumos->map(fn ($i) => ['id' => $i->id, 'nombre' => $i->nom_insumo, 'medida' => $i->medida])),
          medida(id) { const i = this.insumos.find(x => x.id == id); return i ? i.medida : ''; }
      }">
    @csrf
    @if ($producto->exists) @method('PUT') @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            {{-- Datos generales --}}
            <section class="tarjeta p-6">
                <h2 class="mb-4 font-bold">Datos generales</h2>
                <div class="grid gap-4 sm:grid-cols-4">
                    <x-admin.campo nombre="nom_prod" etiqueta="Nombre" :valor="$producto->nom_prod" requerido class="sm:col-span-2" />
                    <x-admin.campo nombre="categoria_id" etiqueta="Categoría" tipo="select" :opciones="$categorias" :valor="$producto->categoria_id" vacio="Seleccione..." requerido />
                    <x-admin.campo nombre="pre_venta" etiqueta="Precio de venta (Bs)" tipo="decimal" :valor="$producto->pre_venta" requerido />
                    <x-admin.campo nombre="barcode" etiqueta="Código de barras" :valor="$producto->barcode" />
                    <x-admin.campo nombre="familia" etiqueta="Familia" :valor="$producto->familia" />
                    <x-admin.campo nombre="cantidad" etiqueta="Cantidad por venta" tipo="decimal" :valor="$producto->cantidad" ayuda="Unidades que agrega cada toque." />
                    <x-admin.campo nombre="estado" etiqueta="Estado" tipo="select" :opciones="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo']" :valor="$producto->estado" />
                    <x-admin.campo nombre="pide_opciones" etiqueta="Pide opciones al vender" tipo="checkbox" :valor="(bool) $producto->presa" class="sm:col-span-2" />
                    <x-admin.campo nombre="por_tiempo" etiqueta="Se cobra por tiempo (billar)" tipo="checkbox" :valor="$producto->tiempo === 'T'" class="sm:col-span-2" />
                </div>
            </section>

            {{-- Configuración por sucursal --}}
            <section class="tarjeta p-6">
                <h2 class="font-bold">Disponibilidad por sucursal</h2>
                <p class="mb-4 text-sm text-tenue">Precio, días y horario en que aparece en el POS, e impresoras donde sale la comanda. Precio vacío = precio general. Horario 00:00 a 00:00 = todo el día.</p>
                <div class="space-y-4">
                    @foreach ($sucursales as $sucursal)
                        @php
                            $c = $configuracion->get($sucursal->id);
                            $base = "sucursales[{$sucursal->id}]";
                            $habilitado = old("sucursales.{$sucursal->id}.habilitado", $c ? true : ! $producto->exists);
                            $dias = old("sucursales.{$sucursal->id}.dias", $c ? collect(PC::DIAS)->keys()->filter(fn ($d) => $c->{$d})->values()->all() : array_keys(PC::DIAS));
                            $impresoras = old("sucursales.{$sucursal->id}.impresoras", $c ? collect(PC::IMPRESORAS)->keys()->filter(fn ($i) => $c->{$i})->values()->all() : []);
                        @endphp
                        <div class="rounded-lg border border-borde p-4" x-data="{ on: @js((bool) $habilitado) }">
                            <label class="flex cursor-pointer items-center gap-2 font-semibold">
                                <input type="hidden" name="{{ $base }}[habilitado]" value="0">
                                <input type="checkbox" name="{{ $base }}[habilitado]" value="1" x-model="on" class="h-4 w-4 accent-marca-500">
                                {{ $sucursal->nombre }}
                                <span class="text-xs font-normal text-tenue" x-show="!on">— no se vende en esta sucursal</span>
                            </label>
                            <div class="mt-3 grid gap-4 sm:grid-cols-4" x-show="on" x-cloak>
                                <x-admin.campo :nombre="$base.'[precio_venta]'" etiqueta="Precio (Bs)" tipo="decimal" :valor="$c && (float) $c->precio_venta > 0 && (float) $c->precio_venta !== (float) $producto->pre_venta ? $c->precio_venta : null" />
                                <x-admin.campo :nombre="$base.'[precio_venta_mayorista]'" etiqueta="Precio mayorista" tipo="decimal" :valor="$c?->precio_venta_mayorista" />
                                <x-admin.campo :nombre="$base.'[hora_inicio]'" etiqueta="Desde" tipo="time" :valor="$c?->hora_inicio ?: '00:00'" />
                                <x-admin.campo :nombre="$base.'[hora_fin]'" etiqueta="Hasta" tipo="time" :valor="$c?->hora_fin ?: '00:00'" />
                                <div class="sm:col-span-2">
                                    <span class="etiqueta">Días</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (PC::DIAS as $clave => $texto)
                                            <label class="cursor-pointer">
                                                <input type="checkbox" name="{{ $base }}[dias][]" value="{{ $clave }}" @checked(in_array($clave, $dias)) class="peer sr-only">
                                                <span class="inline-flex h-8 w-9 items-center justify-center rounded-md border border-borde text-xs font-semibold text-tenue peer-checked:border-marca-500 peer-checked:bg-marca-500 peer-checked:text-white">{{ $texto }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="sm:col-span-2">
                                    <span class="etiqueta">Imprime en</span>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (PC::IMPRESORAS as $clave => $texto)
                                            <label class="cursor-pointer">
                                                <input type="checkbox" name="{{ $base }}[impresoras][]" value="{{ $clave }}" @checked(in_array($clave, $impresoras)) class="peer sr-only">
                                                <span class="inline-flex h-8 items-center rounded-md border border-borde px-2.5 text-xs font-semibold text-tenue peer-checked:border-marca-500 peer-checked:bg-marca-500 peer-checked:text-white">{{ $texto }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <x-admin.campo :nombre="$base.'[f_vence]'" etiqueta="Vence (opcional)" tipo="date" :valor="$c && $c->f_vence && $c->f_vence->year < 2999 ? $c->f_vence->format('Y-m-d') : null" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Receta --}}
            <section class="tarjeta p-6">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold">Receta</h2>
                        <p class="text-sm text-tenue">Insumos que se descuentan del almacén por cada unidad vendida.</p>
                    </div>
                    <button type="button" class="btn-secundario btn-chico" @click="receta.push({ insumo_id: '', cantidad: '' })"><i class="fa fa-plus"></i> Agregar insumo</button>
                </div>
                <template x-for="(item, i) in receta" :key="i">
                    <div class="mb-2 grid grid-cols-[1fr_9rem_auto] gap-2">
                        <select class="campo" :name="`receta[${i}][insumo_id]`" x-model="item.insumo_id" required>
                            <option value="">Seleccione el insumo...</option>
                            <template x-for="ins in insumos" :key="ins.id">
                                <option :value="ins.id" x-text="ins.nombre" :selected="ins.id == item.insumo_id"></option>
                            </template>
                        </select>
                        <div class="relative">
                            <input type="number" step="0.001" min="0" class="campo pr-12" :name="`receta[${i}][cantidad]`" x-model="item.cantidad" placeholder="Cantidad" required>
                            <span class="absolute top-2 right-3 text-xs text-tenue" x-text="medida(item.insumo_id)"></span>
                        </div>
                        <button type="button" class="btn-peligro" @click="receta.splice(i, 1)"><i class="fa fa-trash-o"></i></button>
                    </div>
                </template>
                <p class="text-sm text-tenue" x-show="receta.length === 0">Sin receta: la venta no descuenta inventario.</p>
            </section>
        </div>

        <div class="space-y-5">
            <section class="tarjeta p-6">
                <h2 class="mb-4 font-bold">Botón en el POS</h2>
                <div class="mb-4 flex h-24 flex-col justify-between rounded-lg p-3 text-sm font-bold text-white shadow"
                     style="background: {{ old('colores', $producto->colores) ?: '#337ab7' }}" id="vistaBoton">
                    <span>{{ $producto->nom_prod ?: 'NOMBRE DEL PRODUCTO' }}</span>
                    <span class="self-end rounded-full bg-white/90 px-2 py-0.5 text-xs text-tinta">Bs {{ number_format((float) $producto->pre_venta, 2) }}</span>
                </div>
                <x-admin.campo nombre="colores" etiqueta="Color del botón" tipo="color" :valor="$producto->colores" />
                <div class="mt-4">
                    <label class="etiqueta" for="imagen">Imagen (opcional)</label>
                    @if ($producto->imgnormal)
                        <img src="{{ asset($producto->imgnormal) }}" class="mb-2 h-24 rounded-lg object-cover" alt="">
                        <x-admin.campo nombre="quitar_imagen" etiqueta="Quitar imagen" tipo="checkbox" :valor="false" class="-mt-4 mb-2" />
                    @endif
                    <input type="file" name="imagen" id="imagen" accept="image/*" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:font-semibold">
                </div>
            </section>

            <section class="tarjeta p-6">
                <h2 class="font-bold">Opciones al vender</h2>
                <p class="mb-3 text-sm text-tenue">Una por línea (p. ej. SIN PICANTE). Se muestran si está marcado «Pide opciones al vender».</p>
                <textarea name="opciones" rows="5" class="campo">{{ old('opciones', $producto->opciones->pluck('opcion')->implode("\n")) }}</textarea>
            </section>

            <section class="tarjeta p-6">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-bold">Grupos de reporte</h2>
                    <button type="button" class="btn-secundario btn-chico" @click="grupos.push({ grupo_id: '', factor: 1 })"><i class="fa fa-plus"></i></button>
                </div>
                <template x-for="(item, i) in grupos" :key="i">
                    <div class="mb-2 grid grid-cols-[1fr_5rem_auto] gap-2">
                        <select class="campo" :name="`grupos[${i}][grupo_id]`" x-model="item.grupo_id" required>
                            <option value="">Grupo...</option>
                            @foreach ($grupos as $id => $nombre)
                                <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <input type="number" step="0.01" min="0" class="campo" :name="`grupos[${i}][factor]`" x-model="item.factor" title="Factor" required>
                        <button type="button" class="btn-peligro" @click="grupos.splice(i, 1)"><i class="fa fa-trash-o"></i></button>
                    </div>
                </template>
                <p class="text-sm text-tenue" x-show="grupos.length === 0">Opcional. El factor indica cuántas unidades del grupo suma cada venta.</p>
            </section>
        </div>
    </div>

    <div class="sticky bottom-0 mt-5 flex gap-2 border-t border-borde bg-fondo/95 py-4 backdrop-blur">
        <button class="btn-primario"><i class="fa fa-check"></i> Guardar producto</button>
        <a href="{{ route('admin.productos.index') }}" class="btn-secundario">Volver</a>
    </div>
</form>
@endsection
