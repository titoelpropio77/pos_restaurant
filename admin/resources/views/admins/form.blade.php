@extends('layout')

@section('title', $account->exists ? 'Editar cuenta' : 'Nueva cuenta')

@section('content')
    <form method="POST" action="{{ $account->exists ? route('admins.update', $account) : route('admins.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($account->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="name" label="Nombre" :value="$account->name" required />
            <x-field name="email" label="Correo" type="email" :value="$account->email" required />
            <x-field name="password" label="Contraseña" type="password" :required="! $account->exists" :help="$account->exists ? 'Déjela vacía para no cambiarla.' : 'Mínimo 8 caracteres.'" />
            <x-field name="password_confirmation" label="Repetir contraseña" type="password" />
            <x-field name="role_id" label="Rol" type="select" :options="$roles" :value="$account->roles->first()?->id" empty="Seleccione..." required />
            <x-field name="status" label="Estado" type="select" :options="['ACTIVE' => 'Activo', 'INACTIVE' => 'Inactivo']" :value="$account->status" />
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('admins.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
