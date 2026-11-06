@extends('layout')

@section('title', $role->exists ? 'Editar rol' : 'Nuevo rol')

@section('content')
    <form method="POST" action="{{ $role->exists ? route('roles.update', $role) : route('roles.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($role->exists) @method('PUT') @endif

        <section class="card p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($role->is_protected)
                    <div><span class="label">Nombre</span><p class="font-semibold">{{ $role->label }}</p></div>
                @else
                    <x-field name="label" label="Nombre" :value="$role->label" required />
                @endif
                <div>
                    <span class="label">Ámbito</span>
                    <p class="font-semibold">{{ \App\Models\Role::GUARDS[$role->guard_name] }}</p>
                    @unless ($role->exists)<input type="hidden" name="guard_name" value="{{ $role->guard_name }}">@endunless
                </div>
                <x-field name="description" label="Descripción" :value="$role->description" class="sm:col-span-2" />
            </div>
        </section>

        <section class="card p-6">
            <h2 class="mb-1 font-bold">Permisos</h2>
            @if ($role->isFull())
                <p class="mb-3 text-sm text-muted">Este rol tiene siempre todos los permisos de su ámbito.</p>
            @endif
            @php $checked = old('permissions', $role->exists ? $role->permissions->pluck('id')->all() : []); @endphp
            @foreach (($permissions[$role->guard_name] ?? collect())->groupBy('module') as $module => $list)
                <p class="mt-4 mb-1 text-xs font-semibold tracking-wide text-muted uppercase">{{ $module }}</p>
                <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2">
                    @foreach ($list as $permission)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" class="h-4 w-4 accent-brand-500" @checked(in_array($permission->id, $checked)) @disabled($role->isFull())>
                            {{ $permission->label }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </section>

        <div class="flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('roles.index') }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
