@extends('layout')

@section('title', $permission->exists ? 'Editar permiso' : 'Nuevo permiso')

@section('content')
    <form method="POST" action="{{ $permission->exists ? route('permissions.update', $permission) : route('permissions.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($permission->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($permission->is_protected)
                <div><span class="label">Clave</span><p class="font-mono text-sm">{{ $permission->name }}</p></div>
            @else
                <x-field name="name" label="Clave" :value="$permission->name" required help="Formato modulo.accion, por ejemplo panel.descuentos." />
            @endif
            @if ($permission->exists)
                <div><span class="label">Ámbito</span><p class="font-semibold">{{ \App\Models\Role::GUARDS[$permission->guard_name] }}</p></div>
            @else
                <x-field name="guard_name" label="Ámbito" type="select" :options="\App\Models\Role::GUARDS" :value="$permission->guard_name" required />
            @endif
            <x-field name="label" label="Nombre" :value="$permission->label" required />
            <x-field name="module" label="Módulo" :value="$permission->module" required />
        </div>

        @unless ($permission->is_protected)
            <p class="mt-4 text-xs text-muted">Un permiso nuevo no restringe nada hasta que una ruta de las aplicaciones lo exija.</p>
        @endunless

        <div class="mt-5 flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('permissions.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
