@extends('layout')

@section('title', 'Mi cuenta')

@section('content')
    <form method="POST" action="{{ route('profile.update') }}" class="card max-w-2xl p-6">
        @csrf @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="name" label="Nombre" :value="$account->name" required />
            <x-field name="email" label="Correo" type="email" :value="$account->email" required />
            <x-field name="password_current" label="Contraseña actual" type="password" class="sm:col-span-2" help="Sólo si va a cambiar la contraseña." />
            <x-field name="password" label="Contraseña nueva" type="password" help="Mínimo 8 caracteres." />
            <x-field name="password_confirmation" label="Repetir contraseña" type="password" />
        </div>
        <button class="btn-primary mt-5"><i class="fa fa-check"></i> Guardar</button>
    </form>
@endsection
