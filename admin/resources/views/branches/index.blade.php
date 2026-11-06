@extends('layout')

@section('title', 'Sucursales')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Nombre de la sucursal">
        </div>
        <x-field name="restaurant" label="Restaurante" type="select" :options="$restaurants" :value="request('restaurant')" empty="Todos" class="w-full sm:w-64" />
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @if (auth()->user()->can('branches.create'))
            <a href="{{ route('branches.create', ['restaurant' => request('restaurant')]) }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nueva sucursal</a>
        @endif
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Restaurante</th><th>Sucursal</th><th>Tipo</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($branches as $s)
                    @include('branches.row', ['s' => $s, 'withRestaurant' => true])
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-muted">No hay sucursales.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $branches->links() }}</div>
@endsection
