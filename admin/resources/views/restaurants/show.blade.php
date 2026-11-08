@extends('layout')

@section('title', $restaurant->name)

@section('content')
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-status :value="$restaurant->status" />
        <div class="ml-auto flex gap-2">
            @can('restaurants.update')
                <a href="{{ route('restaurants.edit', $restaurant) }}" class="btn-secondary"><i class="fa fa-pencil"></i> Editar</a>
            @endcan
            @can('restaurants.deactivate')
                <form method="POST" action="{{ route('restaurants.status', $restaurant) }}"
                      onsubmit="return confirm('{{ $restaurant->status === 'ACTIVE' ? '¿Dar de baja? Sus usuarios perderán el acceso de inmediato.' : '¿Reactivar el restaurante?' }}')">
                    @csrf
                    <button @class(['btn-danger' => $restaurant->status === 'ACTIVE', 'btn-primary' => $restaurant->status !== 'ACTIVE'])>
                        <i class="fa fa-power-off"></i> {{ $restaurant->status === 'ACTIVE' ? 'Dar de baja' : 'Reactivar' }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <section class="card p-5">
            <h2 class="mb-3 font-bold">Datos</h2>
            @if ($restaurant->logo || $restaurant->background_color)
                <div class="mb-4 flex h-28 items-center justify-center rounded-xl border border-line p-3" style="background: {{ $restaurant->background_color ?: '#FFFFFF' }}"
                     title="Fondo de la tienda: {{ $restaurant->background_color ?: 'sin definir' }}">
                    @if ($restaurant->logo)
                        <img src="{{ $restaurant->logo }}" class="max-h-full max-w-full object-contain" alt="Logo de {{ $restaurant->name }}">
                    @endif
                </div>
            @endif
            <dl class="space-y-2 text-sm">
                @foreach (['NIT' => $restaurant->tax_id, 'Propietarios' => $restaurant->partners->pluck('name')->join(', '), 'Correo' => $restaurant->email, 'Teléfono' => $restaurant->phone, 'Dirección' => $restaurant->address, 'Actividad' => $restaurant->activity] as $k => $v)
                    <div class="flex justify-between gap-3"><dt class="text-muted">{{ $k }}</dt><dd class="text-right font-medium">{{ $v ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="card overflow-x-auto xl:col-span-2">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Sucursales</h2>
                @can('branches.create')
                    <a href="{{ route('branches.create', ['restaurant' => $restaurant->id]) }}" class="btn-secondary btn-small"><i class="fa fa-plus"></i> Sucursal</a>
                @endcan
            </div>
            <table class="data-table">
                <thead><tr><th>Sucursal</th><th>Tipo</th><th>Estado</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($restaurant->branches as $s)
                        @include('branches.row', ['s' => $s, 'withRestaurant' => false])
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="card overflow-x-auto xl:col-span-3">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Usuarios</h2>
                @if (auth()->user()->can('users.create'))
                    <a href="{{ route('users.create', ['restaurant' => $restaurant->id]) }}" class="btn-secondary btn-small"><i class="fa fa-plus"></i> Usuario</a>
                @endif
            </div>
            <table class="data-table">
                <thead><tr><th>Correo</th><th>Nombre</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($restaurant->users as $u)
                        <tr><td class="font-semibold">{{ $u->email }}</td><td>{{ $u->name }}</td><td>{{ $u->roles->first()?->label ?? '—' }}</td><td><x-status :value="$u->status" /></td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-muted">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
