@extends('admin.layout')

@section('titulo', $usuario->exists ? 'Editar usuario' : 'Nuevo usuario')

@section('contenido')
    <form method="POST" class="max-w-4xl space-y-5"
          action="{{ $usuario->exists ? route('admin.usuarios.update', $usuario) : route('admin.usuarios.store') }}">
        @csrf
        @if ($usuario->exists) @method('PUT') @endif

        <section class="tarjeta p-6">
            <h2 class="mb-4 font-bold">Datos de acceso</h2>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-admin.campo nombre="nombres" etiqueta="Nombre completo" :valor="$usuario->nombres" requerido class="sm:col-span-2" />
                <x-admin.campo nombre="usuario" etiqueta="Usuario" :valor="$usuario->usuario" requerido ayuda="Sin espacios. No distingue mayúsculas." />
                <x-admin.campo nombre="cargo" etiqueta="Cargo" :valor="$usuario->cargo" />
                <x-admin.campo nombre="password" etiqueta="Contraseña" tipo="password" :requerido="! $usuario->exists" :ayuda="$usuario->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 4 caracteres.'" />
                <x-admin.campo nombre="password_confirmation" etiqueta="Repetir contraseña" tipo="password" />
                <x-admin.campo nombre="nivel" etiqueta="Nivel" tipo="select" :opciones="[1 => 'Administrador', 2 => 'Cajero']" :valor="$usuario->nivel" />
                <x-admin.campo nombre="estado" etiqueta="Estado" tipo="select" :opciones="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo']" :valor="$usuario->estado" />
            </div>
        </section>

        <section class="tarjeta p-6">
            <h2 class="font-bold">Punto de venta</h2>
            <p class="mb-4 text-sm text-tenue">Sucursal con la que ingresa al POS y almacén del que se descuentan las recetas.</p>
            <div class="grid gap-4 sm:grid-cols-4">
                <x-admin.campo nombre="sucursal_id" etiqueta="Sucursal" tipo="select" :opciones="$sucursales" :valor="$usuario->puntoVenta?->sucursal_id" vacio="Seleccione..." requerido class="sm:col-span-2" />
                <x-admin.campo nombre="almacen_por_defecto_id" etiqueta="Almacén por defecto" tipo="select" :opciones="$almacenes" :valor="$usuario->puntoVenta?->almacen_por_defecto_id" vacio="Ninguno" class="sm:col-span-2" />
                <x-admin.campo nombre="puede_vender" etiqueta="Puede vender" tipo="checkbox" :valor="$usuario->puntoVenta?->puede_vender ?? true" class="sm:col-span-2" />
                <x-admin.campo nombre="venta_multialmacen" etiqueta="Puede cambiar de sucursal en el POS" tipo="checkbox" :valor="$usuario->puntoVenta?->venta_multialmacen ?? false" class="sm:col-span-2" />
            </div>
        </section>

        <div class="flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('admin.usuarios.index') }}" class="btn-secundario">Cancelar</a>
        </div>
    </form>
@endsection
