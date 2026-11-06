@extends('layout')

@section('titulo', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')

@section('contenido')
    <form method="POST" action="{{ $sucursal->exists ? route('sucursales.update', $sucursal) : route('sucursales.store') }}" class="tarjeta max-w-3xl p-6">
        @csrf
        @if ($sucursal->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($sucursal->exists)
                <div class="sm:col-span-2"><span class="etiqueta">Restaurante</span><p class="font-semibold">{{ $sucursal->empresa->nombre }}</p></div>
            @else
                <x-campo nombre="empresa_id" etiqueta="Restaurante" tipo="select" :opciones="$restaurantes" :valor="$sucursal->empresa_id" vacio="Seleccione..." requerido class="sm:col-span-2" />
            @endif
            <x-campo nombre="nombre" etiqueta="Nombre" :valor="$sucursal->nombre" requerido />
            <x-campo nombre="telefono" etiqueta="Teléfono" :valor="$sucursal->telefono" />
            <x-campo nombre="ciudad_id" etiqueta="Ciudad" tipo="select" :opciones="$ciudades" :valor="$sucursal->ciudad_id" vacio="—" />
            <x-campo nombre="ip_impresion" etiqueta="IP del agente de impresión" :valor="$sucursal->ip_impresion" />
            <x-campo nombre="direccion" etiqueta="Dirección" :valor="$sucursal->direccion" class="sm:col-span-2" />
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('sucursales.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
