@extends('admin.layout')

@section('title', 'Nuevo movimiento de inventario')

@section('content')
<form method="POST" action="{{ route('admin.inventory.store') }}" class="max-w-5xl space-y-5"
      x-data="{
          type: @js(old('type', $type)),
          supplies: @js($supplies),
          lines: @js(old('lines', [['supply_id' => '', 'quantity' => '', 'cost' => '']])),
          supply(id) { return this.supplies.find(i => i.id == id) || {}; },
          total() { return this.lines.reduce((s, l) => s + (parseFloat(l.quantity) || 0) * (parseFloat(this.type === 'COMPRA' ? l.cost : this.supply(l.supply_id).cost) || 0), 0); }
      }">
    @csrf

    <section class="card p-6">
        <div class="mb-5 flex flex-wrap gap-2">
            @foreach ($types as $key => $text)
                <label class="cursor-pointer">
                    <input type="radio" name="type" value="{{ $key }}" x-model="type" class="peer sr-only">
                    <span class="inline-flex rounded-lg border border-line px-4 py-2 text-sm font-semibold text-muted peer-checked:border-brand-500 peer-checked:bg-brand-500 peer-checked:text-panel">{{ $text }}</span>
                </label>
            @endforeach
        </div>
        <p class="mb-4 text-sm text-muted" x-show="type === 'AJUSTE'">Escriba la cantidad <strong>contada</strong> de cada insumo: el sistema registra la diferencia con el stock actual.</p>

        <div class="grid gap-4 sm:grid-cols-4">
            <x-admin.field name="date" label="Fecha" type="date" :value="today()->format('Y-m-d')" required />
            <div class="sm:col-span-2">
                <label class="label" for="warehouse_id"><span x-text="type === 'TRASPASO' ? 'Almacén de origen' : 'Almacén'"></span> <span class="text-brand-600">*</span></label>
                <select name="warehouse_id" id="warehouse_id" class="field" required>
                    <option value="">Seleccione...</option>
                    @foreach ($warehouses as $id => $name)<option value="{{ $id }}" @selected(old('warehouse_id') == $id)>{{ $name }}</option>@endforeach
                </select>
            </div>
            <x-admin.field name="movement_reason_id" label="Motivo" type="select" :options="$reasons" empty="—" />
            <div class="sm:col-span-2" x-show="type === 'TRASPASO'" x-cloak>
                <label class="label" for="destination_warehouse_id">Almacén de destino <span class="text-brand-600">*</span></label>
                <select name="destination_warehouse_id" id="destination_warehouse_id" class="field" :disabled="type !== 'TRASPASO'">
                    <option value="">Seleccione...</option>
                    @foreach ($warehouses as $id => $name)<option value="{{ $id }}" @selected(old('destination_warehouse_id') == $id)>{{ $name }}</option>@endforeach
                </select>
            </div>
            <x-admin.field name="description" label="Glosa / detalle" class="sm:col-span-2" />
        </div>
    </section>

    <section class="card p-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold">Insumos</h2>
            <button type="button" class="btn-secondary btn-small" @click="lines.push({ supply_id: '', quantity: '', cost: '' })"><i class="fa fa-plus"></i> Agregar fila</button>
        </div>
        <div class="mb-1 hidden grid-cols-[1fr_10rem_10rem_8rem_auto] gap-2 text-xs font-semibold text-muted sm:grid">
            <span>Insumo</span><span x-text="type === 'AJUSTE' ? 'Cantidad contada' : 'Cantidad'"></span><span x-text="type === 'COMPRA' ? 'Costo unitario (Bs)' : 'Costo prom.'"></span><span class="text-right">Subtotal</span><span></span>
        </div>
        <template x-for="(l, i) in lines" :key="i">
            <div class="mb-2 grid gap-2 sm:grid-cols-[1fr_10rem_10rem_8rem_auto]">
                <select class="field" :name="`lines[${i}][supply_id]`" x-model="l.supply_id" required>
                    <option value="">Seleccione el insumo...</option>
                    <template x-for="ins in supplies" :key="ins.id">
                        <option :value="ins.id" x-text="ins.name" :selected="ins.id == l.supply_id"></option>
                    </template>
                </select>
                <div class="relative">
                    <input type="number" step="0.001" min="0" class="field pr-12" :name="`lines[${i}][quantity]`" x-model="l.quantity" required>
                    <span class="absolute top-2 right-3 text-xs text-muted" x-text="supply(l.supply_id).measure"></span>
                </div>
                <input type="number" step="0.0001" min="0" class="field" :name="`lines[${i}][cost]`" x-model="l.cost" x-show="type === 'COMPRA'" :required="type === 'COMPRA'" :disabled="type !== 'COMPRA'">
                <input type="text" class="field bg-slate-50" readonly x-show="type !== 'COMPRA'" :value="(supply(l.supply_id).cost || 0).toFixed(2)">
                <span class="num self-center font-semibold" x-text="((parseFloat(l.quantity) || 0) * (parseFloat(type === 'COMPRA' ? l.cost : supply(l.supply_id).cost) || 0)).toFixed(2)"></span>
                <button type="button" class="btn-danger" @click="lines.splice(i, 1)" :disabled="lines.length === 1"><i class="fa fa-trash-o"></i></button>
            </div>
        </template>
        <p class="mt-3 border-t border-line pt-3 text-right text-sm" x-show="type !== 'AJUSTE'">Importe total: <strong class="text-lg" x-text="'Bs ' + total().toFixed(2)"></strong></p>
    </section>

    <div class="flex gap-2">
        <button class="btn-primary"><i class="fa fa-check"></i> Registrar movimiento</button>
        <a href="{{ route('admin.inventory.movements') }}" class="btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
