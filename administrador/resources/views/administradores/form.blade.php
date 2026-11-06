@extends('layout')

@section('titulo', $cuenta->exists ? 'Editar cuenta' : 'Nueva cuenta')

@section('contenido')
    <form method="POST" action="{{ $cuenta->exists ? route('administradores.update', $cuenta) : route('administradores.store') }}" class="tarjeta max-w-2xl p-6">
        @csrf
        @if ($cuenta->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nombre="nombre" etiqueta="Nombre" :valor="$cuenta->nombre" requerido />
            <x-campo nombre="correo" etiqueta="Correo" tipo="email" :valor="$cuenta->correo" requerido />
            <x-campo nombre="password" etiqueta="Contraseña" tipo="password" :requerido="! $cuenta->exists" :ayuda="$cuenta->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 8 caracteres.'" />
            <x-campo nombre="password_confirmation" etiqueta="Repetir contraseña" tipo="password" />
            <x-campo nombre="rol_id" etiqueta="Rol" tipo="select" :opciones="$roles" :valor="$cuenta->rol_id" vacio="Seleccione..." requerido />
            <x-campo nombre="estado" etiqueta="Estado" tipo="select" :opciones="['ACTIVO' => 'Activo', 'INACTIVO' => 'Inactivo']" :valor="$cuenta->estado" />
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('administradores.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
