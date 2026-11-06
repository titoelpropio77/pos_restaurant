@extends('layout')

@section('titulo', 'Mi cuenta')

@section('contenido')
    <form method="POST" action="{{ route('perfil.update') }}" class="tarjeta max-w-2xl p-6">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <x-campo nombre="nombre" etiqueta="Nombre" :valor="$cuenta->nombre" requerido />
            <x-campo nombre="correo" etiqueta="Correo" tipo="email" :valor="$cuenta->correo" requerido />
            <x-campo nombre="password_actual" etiqueta="Contraseña actual" tipo="password" class="sm:col-span-2" ayuda="Sólo si va a cambiar la contraseña." />
            <x-campo nombre="password" etiqueta="Contraseña nueva" tipo="password" ayuda="Mínimo 8 caracteres." />
            <x-campo nombre="password_confirmation" etiqueta="Repetir contraseña" tipo="password" />
        </div>
        <button class="btn-primario mt-5"><i class="fa fa-check"></i> Guardar</button>
    </form>
@endsection
