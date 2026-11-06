@extends('layout')

@section('title', $restaurant->exists ? 'Editar restaurante' : 'Nuevo restaurante')

@section('content')
    <form method="POST" action="{{ $restaurant->exists ? route('restaurants.update', $restaurant) : route('restaurants.store') }}" class="max-w-4xl space-y-4">
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
                @foreach (\App\Models\Restaurant::OPTIONS as $key => [$text])
                    <x-field :name="'options['.$key.']'" :label="$text" type="checkbox" :value="$restaurant->options[$key] ?? false" />
                @endforeach
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ $restaurant->exists ? route('restaurants.show', $restaurant) : route('restaurants.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
