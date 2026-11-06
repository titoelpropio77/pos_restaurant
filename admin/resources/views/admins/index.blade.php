@extends('layout')

@section('title', 'Cuentas del sistema')

@section('content')
    @can('admins.create')
        <div class="mb-4 flex"><a href="{{ route('admins.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nueva cuenta</a></div>
    @endcan

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Último ingreso</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @foreach ($accounts as $c)
                    <tr>
                        <td class="font-semibold">{{ $c->name }}</td>
                        <td>{{ $c->email }}</td>
                        <td>{{ $c->roles->first()?->label }}</td>
                        <td>{{ $c->last_login_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><x-status :value="$c->status" /></td>
                        <td class="whitespace-nowrap">
                            @can('admins.update')
                            <a href="{{ route('admins.edit', $c) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @if ($c->isActive())
                                @can('admins.deactivate')
                                <form method="POST" action="{{ route('admins.destroy', $c) }}" class="inline" onsubmit="return confirm('¿Dar de baja esta cuenta?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-small"><i class="fa fa-ban"></i> Baja</button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $accounts->links() }}</div>
@endsection
