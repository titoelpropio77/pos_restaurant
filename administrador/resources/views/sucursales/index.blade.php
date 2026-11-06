@extends('layout')

@section('titulo', 'Sucursales')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Nombre de la sucursal">
        </div>
        <x-campo nombre="restaurante" etiqueta="Restaurante" tipo="select" :opciones="$restaurantes" :valor="request('restaurante')" vacio="Todos" class="w-full sm:w-64" />
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        @if (auth()->user()->puede('restaurantes.gestionar'))
            <a href="{{ route('sucursales.create', ['restaurante' => request('restaurante')]) }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nueva sucursal</a>
        @endif
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Restaurante</th><th>Sucursal</th><th>Tipo</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($sucursales as $s)
                    @include('sucursales.fila', ['s' => $s, 'conRestaurante' => true])
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-tenue">No hay sucursales.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $sucursales->links() }}</div>
@endsection
