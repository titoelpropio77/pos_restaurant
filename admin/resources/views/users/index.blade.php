@extends('layout')

@section('title', 'Usuarios de restaurantes')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Nombre, correo o código">
        </div>
        <x-field name="restaurant" label="Restaurante" type="select" :options="$restaurants" :value="request('restaurant')" empty="Todos" class="w-full sm:w-64" />
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @can('users.create')
            <a href="{{ route('users.create', ['restaurant' => request('restaurant')]) }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo usuario</a>
        @endcan
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Correo</th><th>Nombre</th><th>Restaurante</th><th>Sucursal</th><th>Rol</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-semibold">{{ $u->email }}<span class="block text-xs font-normal text-muted">{{ $u->username }}</span></td>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->restaurants->pluck('name')->join(', ') ?: '—' }}</td>
                        <td>{{ $u->pointsOfSale->map(fn ($p) => $p->branch?->name)->filter()->join(', ') ?: '—' }}</td>
                        <td>{{ $u->roles->first()?->label ?? '—' }}</td>
                        <td><x-status :value="$u->status" /></td>
                        <td class="whitespace-nowrap">
                            @can('users.update')
                                <a href="{{ route('users.edit', $u) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @can('users.deactivate')
                                <form method="POST" action="{{ route('users.status', $u) }}" class="inline">
                                    @csrf
                                    <button class="btn-secondary btn-small" title="{{ $u->status === 'ACTIVE' ? 'Dar de baja' : 'Reactivar' }}"><i class="fa fa-power-off"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
