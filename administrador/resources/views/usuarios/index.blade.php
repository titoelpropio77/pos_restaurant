@extends('layout')

@section('titulo', 'Usuarios de restaurantes')

@section('contenido')
    @php $gestiona = auth()->user()->puede('usuarios.gestionar'); @endphp

    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-64">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Nombre o usuario">
        </div>
        <x-campo nombre="restaurante" etiqueta="Restaurante" tipo="select" :opciones="$restaurantes" :valor="request('restaurante')" vacio="Todos" class="w-full sm:w-64" />
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        @if ($gestiona)
            <a href="{{ route('usuarios.create', ['restaurante' => request('restaurante')]) }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo usuario</a>
        @endif
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Usuario</th><th>Nombre</th><th>Restaurante</th><th>Sucursal</th><th>Rol</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($usuarios as $u)
                    <tr>
                        <td class="font-semibold">{{ $u->usuario }}</td>
                        <td>{{ $u->nombres }}</td>
                        <td>{{ $u->empresa->nombre }}</td>
                        <td>{{ $u->puntoVenta?->sucursal?->nombre ?? '—' }}</td>
                        <td>{{ $u->rol?->nombre ?? '—' }}</td>
                        <td><x-estado :valor="$u->estado" /></td>
                        <td class="whitespace-nowrap">
                            @if ($gestiona)
                                <a href="{{ route('usuarios.edit', $u) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                                <form method="POST" action="{{ route('usuarios.estado', $u) }}" class="inline">
                                    @csrf
                                    <button class="btn-secundario btn-chico" title="{{ $u->estado === 'ACTIVO' ? 'Dar de baja' : 'Reactivar' }}"><i class="fa fa-power-off"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-tenue">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
