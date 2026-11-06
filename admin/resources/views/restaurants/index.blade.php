@extends('layout')

@section('title', 'Restaurantes')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Nombre del restaurante">
        </div>
        <div>
            <label class="label" for="status">Estado</label>
            <select name="status" id="status" class="field">
                <option value="">Todos</option>
                @foreach (['ACTIVE' => 'Activos', 'INACTIVE' => 'Inactivos'] as $v => $t)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @if (auth()->user()->can('restaurants.create'))
            <a href="{{ route('restaurants.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo restaurante</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Restaurante</th><th>NIT</th><th>Casa matriz</th><th class="num">Sucursales</th><th class="num">Usuarios</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($restaurants as $r)
                    <tr>
                        <td class="font-semibold">{{ $r->name }}<span class="block text-xs font-normal text-muted">{{ $r->partners->pluck('name')->join(', ') }}</span></td>
                        <td>{{ $r->tax_id ?: '—' }}</td>
                        <td>{{ $r->main?->name ?? '—' }}</td>
                        <td class="num">{{ $r->branches_count }}</td>
                        <td class="num">{{ $r->users_count }}</td>
                        <td><x-status :value="$r->status" /></td>
                        <td class="whitespace-nowrap"><a href="{{ route('restaurants.show', $r) }}" class="btn-secondary btn-small"><i class="fa fa-eye"></i> Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-muted">No hay restaurantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $restaurants->links() }}</div>
@endsection
