@extends('layout')

@section('title', 'Roles')

@section('content')
    @can('roles.create')
    <div class="mb-4 flex justify-end gap-2">
        <a href="{{ route('roles.create', ['guard' => 'web']) }}" class="btn-primary"><i class="fa fa-plus"></i> Rol de restaurante</a>
        <a href="{{ route('roles.create', ['guard' => 'admin']) }}" class="btn-secondary"><i class="fa fa-plus"></i> Rol del sistema</a>
    </div>
    @endcan

    @foreach (\App\Models\Role::GUARDS as $guard => $title)
        <section class="card mb-4 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $title }}</h2>
            <table class="data-table">
                <thead><tr><th>Rol</th><th class="num">Permisos</th><th class="num">Cuentas</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($roles[$guard] ?? [] as $role)
                        <tr>
                            <td class="font-semibold">{{ $role->label }}
                                @if ($role->is_protected)<i class="fa fa-lock text-xs text-muted" title="Rol del sistema"></i>@endif
                                <span class="block text-xs font-normal text-muted">{{ $role->description }}</span>
                            </td>
                            <td class="num">{{ $role->permissions_count }}</td>
                            <td class="num">{{ $role->admins_count + $role->restaurant_users_count }}</td>
                            <td class="whitespace-nowrap">
                                @can('roles.update')
                                <a href="{{ route('roles.edit', $role) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                                @endcan
                                @unless ($role->is_protected)
                                    @can('roles.delete')
                                    <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline" onsubmit="return confirm('¿Eliminar este rol?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-danger btn-small"><i class="fa fa-trash"></i></button>
                                    </form>
                                    @endcan
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
@endsection
