@extends('admin.layout')

@section('titulo', 'Nuevo movimiento de inventario')

@section('contenido')
<form method="POST" action="{{ route('admin.inventario.store') }}" class="max-w-5xl space-y-5"
      x-data="{
          tipo: @js(old('tipo', $tipo)),
          insumos: @js($insumos),
          lineas: @js(old('lineas', [['insumo_id' => '', 'cantidad' => '', 'costo' => '']])),
          insumo(id) { return this.insumos.find(i => i.id == id) || {}; },
          total() { return this.lineas.reduce((s, l) => s + (parseFloat(l.cantidad) || 0) * (parseFloat(this.tipo === 'COMPRA' ? l.costo : this.insumo(l.insumo_id).costo) || 0), 0); }
      }">
    @csrf

    <section class="tarjeta p-6">
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($tipos as $clave => $texto)
                <label class="cursor-pointer">
                    <input type="radio" name="tipo" value="{{ $clave }}" x-model="tipo" class="peer sr-only">
                    <span class="inline-flex rounded-lg border border-borde px-4 py-2 text-sm font-semibold text-tenue peer-checked:border-marca-500 peer-checked:bg-marca-500 peer-checked:text-white">{{ $texto }}</span>
                </label>
            @endforeach
        </div>
        <p class="mb-4 text-sm text-tenue" x-show="tipo === 'AJUSTE'">Escriba la cantidad <strong>contada</strong> de cada insumo: el sistema registra la diferencia con el stock actual.</p>

        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.campo nombre="fecha" etiqueta="Fecha" tipo="date" :valor="today()->format('Y-m-d')" requerido />
            <div class="sm:col-span-2">
                <label class="etiqueta" for="almacen_id"><span x-text="tipo === 'TRASPASO' ? 'Almacén de origen' : 'Almacén'"></span> <span class="text-marca-600">*</span></label>
                <select name="almacen_id" id="almacen_id" class="campo" required>
                    <option value="">Seleccione...</option>
                    @foreach ($almacenes as $id => $nombre)<option value="{{ $id }}" @selected(old('almacen_id') == $id)>{{ $nombre }}</option>@endforeach
                </select>
            </div>
            <x-admin.campo nombre="motivo_movimiento_id" etiqueta="Motivo" tipo="select" :opciones="$motivos" vacio="—" />
            <div class="sm:col-span-2" x-show="tipo === 'TRASPASO'" x-cloak>
                <label class="etiqueta" for="almacen_destino_id">Almacén de destino <span class="text-marca-600">*</span></label>
                <select name="almacen_destino_id" id="almacen_destino_id" class="campo" :disabled="tipo !== 'TRASPASO'">
                    <option value="">Seleccione...</option>
                    @foreach ($almacenes as $id => $nombre)<option value="{{ $id }}" @selected(old('almacen_destino_id') == $id)>{{ $nombre }}</option>@endforeach
                </select>
            </div>
            <x-admin.campo nombre="glosa" etiqueta="Glosa / detalle" class="sm:col-span-2" />
        </div>
    </section>

    <section class="tarjeta p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Insumos</h2>
            <button type="button" class="btn-secundario btn-chico" @click="lineas.push({ insumo_id: '', cantidad: '', costo: '' })"><i class="fa fa-plus"></i> Agregar fila</button>
        </div>
        <div class="mb-1 hidden grid-cols-[1fr_10rem_10rem_8rem_auto] gap-2 text-xs font-semibold text-tenue sm:grid">
            <span>Insumo</span><span x-text="tipo === 'AJUSTE' ? 'Cantidad contada' : 'Cantidad'"></span><span x-text="tipo === 'COMPRA' ? 'Costo unitario (Bs)' : 'Costo prom.'"></span><span class="text-right">Subtotal</span><span></span>
        </div>
        <template x-for="(l, i) in lineas" :key="i">
            <div class="mb-2 grid gap-2 sm:grid-cols-[1fr_10rem_10rem_8rem_auto]">
                <select class="campo" :name="`lineas[${i}][insumo_id]`" x-model="l.insumo_id" required>
                    <option value="">Seleccione el insumo...</option>
                    <template x-for="ins in insumos" :key="ins.id">
                        <option :value="ins.id" x-text="ins.nombre" :selected="ins.id == l.insumo_id"></option>
                    </template>
                </select>
                <div class="relative">
                    <input type="number" step="0.001" min="0" class="campo pr-12" :name="`lineas[${i}][cantidad]`" x-model="l.cantidad" required>
                    <span class="absolute top-2 right-3 text-xs text-tenue" x-text="insumo(l.insumo_id).medida"></span>
                </div>
                <input type="number" step="0.0001" min="0" class="campo" :name="`lineas[${i}][costo]`" x-model="l.costo" x-show="tipo === 'COMPRA'" :required="tipo === 'COMPRA'" :disabled="tipo !== 'COMPRA'">
                <input type="text" class="campo bg-slate-50" readonly x-show="tipo !== 'COMPRA'" :value="(insumo(l.insumo_id).costo || 0).toFixed(2)">
                <span class="num self-center font-semibold" x-text="((parseFloat(l.cantidad) || 0) * (parseFloat(tipo === 'COMPRA' ? l.costo : insumo(l.insumo_id).costo) || 0)).toFixed(2)"></span>
                <button type="button" class="btn-peligro" @click="lineas.splice(i, 1)" :disabled="lineas.length === 1"><i class="fa fa-trash-o"></i></button>
            </div>
        </template>
        <p class="mt-3 border-t border-borde pt-3 text-right text-sm" x-show="tipo !== 'AJUSTE'">Importe total: <strong class="text-lg" x-text="'Bs ' + total().toFixed(2)"></strong></p>
    </section>

    <div class="flex gap-2">
        <button class="btn-primario"><i class="fa fa-check"></i> Registrar movimiento</button>
        <a href="{{ route('admin.inventario.movimientos') }}" class="btn-secundario">Cancelar</a>
    </div>
</form>
@endsection
