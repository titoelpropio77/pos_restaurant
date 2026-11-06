@extends('admin.layout')

@section('title', 'Usuarios')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Nombre, correo o código">
        </div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @can('users.create')
        <a href="{{ route('admin.users.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo usuario</a>
        @endcan
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr><th>Correo</th><th>Nombre</th><th>Nivel</th><th>Sucursal</th><th>Puede vender</th><th>Estado</th><th class="w-1"></th></tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    <tr>
                        <td class="font-semibold">{{ $u->email }}<span class="block text-xs font-normal text-muted">{{ $u->username }}</span></td>
                        <td>{{ $u->name }} <span class="block text-xs text-muted">{{ $u->position }}</span></td>
                        <td>
                            <span @class(['badge', 'bg-brand-100 text-brand-700' => $u->canAccessPanel(), 'bg-slate-100 text-slate-700' => ! $u->canAccessPanel()])>
                                {{ $u->roles->first()?->label ?? '—' }}
                            </span>
                        </td>
                        <td>{{ $u->pointOfSale?->branch?->name ?? '—' }} @if ($u->pointOfSale?->multi_branch_sales)<span class="text-xs text-muted">(multi-sucursal)</span>@endif</td>
                        <td>{!! $u->pointOfSale?->can_sell ? '<i class="fa fa-check text-green-600"></i>' : '<i class="fa fa-times text-red-500"></i>' !!}</td>
                        <td><x-admin.status :value="$u->status" /></td>
                        <td class="whitespace-nowrap text-right">
                            @can('users.update')
                            <a href="{{ route('admin.users.edit', $u) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @if ($u->status === 'ACTIVE' && ! $u->is(auth()->user()))
                                @can('users.delete')
                                <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('¿Dar de baja a este usuario?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-small" title="Dar de baja"><i class="fa fa-ban"></i></button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-muted">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
