@extends('layout')

@section('title', $branch->exists ? 'Editar sucursal' : 'Nueva sucursal')

@section('content')
    <form method="POST" action="{{ $branch->exists ? route('branches.update', $branch) : route('branches.store') }}" class="card max-w-3xl p-6">
        @csrf
        @if ($branch->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($branch->exists)
                <div class="sm:col-span-2"><span class="label">Restaurante</span><p class="font-semibold">{{ $branch->restaurant->name }}</p></div>
            @else
                <x-field name="restaurant_id" label="Restaurante" type="select" :options="$restaurants" :value="$branch->restaurant_id" empty="Seleccione..." required class="sm:col-span-2" />
            @endif
            <x-field name="name" label="Nombre" :value="$branch->name" required />
            <x-field name="phone" label="Teléfono" :value="$branch->phone" />
            <x-field name="city_id" label="Ciudad" type="select" :options="$cities" :value="$branch->city_id" empty="—" />
            <x-field name="print_ip" label="IP del agente de impresión" :value="$branch->print_ip" />
            <x-field name="address" label="Dirección" :value="$branch->address" class="sm:col-span-2" />
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('branches.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
