@extends('layout')

@section('title', 'Permisos')

@section('content')
    @can('permissions.create')
        <div class="mb-4 flex"><a href="{{ route('permissions.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo permiso</a></div>
    @endcan

    @foreach (\App\Models\Role::GUARDS as $guard => $title)
        <section class="card mb-4 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $title }}</h2>
            <table class="data-table">
                <thead><tr><th>Clave</th><th>Permiso</th><th>Módulo</th><th class="num">Roles</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($permissions[$guard] ?? [] as $p)
                        <tr>
                            <td class="font-mono text-xs">{{ $p->name }} @if ($p->is_protected)<i class="fa fa-lock text-muted" title="Lo usa el sistema"></i>@endif</td>
                            <td>{{ $p->label }}</td>
                            <td>{{ $p->module }}</td>
                            <td class="num">{{ $p->roles_count }}</td>
                            <td class="whitespace-nowrap">
                                @can('permissions.update')
                                <a href="{{ route('permissions.edit', $p) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                                @endcan
                                @unless ($p->is_protected)
                                    @can('permissions.delete')
                                    <form method="POST" action="{{ route('permissions.destroy', $p) }}" class="inline" onsubmit="return confirm('¿Eliminar este permiso?')">
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
