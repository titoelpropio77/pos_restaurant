@extends('layout')

@section('titulo', $restaurante->exists ? 'Editar restaurante' : 'Nuevo restaurante')

@section('contenido')
    <form method="POST" action="{{ $restaurante->exists ? route('restaurantes.update', $restaurante) : route('restaurantes.store') }}" class="max-w-4xl space-y-4">
        @csrf
        @if ($restaurante->exists) @method('PUT') @endif

        <section class="tarjeta p-6">
            <h2 class="mb-4 font-bold">Datos del restaurante</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-campo nombre="nombre" etiqueta="Nombre" :valor="$restaurante->nombre" requerido class="sm:col-span-2" />
                <x-campo nombre="nit" etiqueta="NIT" :valor="$restaurante->nit" />
                <x-campo nombre="propietario" etiqueta="Propietario" :valor="$restaurante->propietario" />
                <x-campo nombre="correo" etiqueta="Correo" tipo="email" :valor="$restaurante->correo" />
                <x-campo nombre="telefono" etiqueta="Teléfono" :valor="$restaurante->telefono" />
                <x-campo nombre="direccion" etiqueta="Dirección" :valor="$restaurante->direccion" class="sm:col-span-2" />
                <x-campo nombre="estado" etiqueta="Estado" tipo="select" :opciones="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo']" :valor="$restaurante->estado" />
            </div>
        </section>

        @unless ($restaurante->exists)
            <section class="tarjeta p-6">
                <h2 class="font-bold">Casa matriz</h2>
                <p class="mb-4 text-sm text-tenue">Sucursal principal: se crea con su almacén y su numeración de ventas.</p>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-campo nombre="matriz_nombre" etiqueta="Nombre" valor="CASA MATRIZ" requerido />
                    <x-campo nombre="matriz_ciudad_id" etiqueta="Ciudad" tipo="select" :opciones="$ciudades" vacio="—" />
                    <x-campo nombre="matriz_telefono" etiqueta="Teléfono" />
                    <x-campo nombre="matriz_direccion" etiqueta="Dirección" class="sm:col-span-3" />
                </div>
            </section>

            <section class="tarjeta p-6">
                <h2 class="font-bold">Usuario dueño</h2>
                <p class="mb-4 text-sm text-tenue">Con este usuario el restaurante ingresa al POS y a su panel. El usuario y la contraseña no distinguen mayúsculas.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-campo nombre="dueno_nombres" etiqueta="Nombre completo" requerido />
                    <x-campo nombre="dueno_usuario" etiqueta="Usuario" requerido ayuda="Único en todo el sistema, sin espacios." />
                    <x-campo nombre="dueno_password" etiqueta="Contraseña" tipo="password" requerido ayuda="Mínimo 4 caracteres." />
                    <x-campo nombre="dueno_password_confirmation" etiqueta="Repetir contraseña" tipo="password" requerido />
                </div>
            </section>
        @endunless

        <section class="tarjeta p-6">
            <h2 class="mb-2 font-bold">Opciones del POS</h2>
            <div class="grid gap-x-4 sm:grid-cols-2">
                @foreach (\App\Models\Empresa::OPCIONES as $clave => [$texto])
                    <x-campo :nombre="'opciones['.$clave.']'" :etiqueta="$texto" tipo="checkbox" :valor="$restaurante->opciones[$clave] ?? false" />
                @endforeach
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ $restaurante->exists ? route('restaurantes.show', $restaurante) : route('restaurantes.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
