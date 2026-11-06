@extends('layout')

@section('titulo', 'Restaurantes')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Nombre del restaurante">
        </div>
        <div>
            <label class="etiqueta" for="estado">Estado</label>
            <select name="estado" id="estado" class="campo">
                <option value="">Todos</option>
                @foreach (['ACTIVO' => 'Activos', 'INACTIVO' => 'Inactivos'] as $v => $t)
                    <option value="{{ $v }}" @selected(request('estado') === $v)>{{ $t }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        @if (auth()->user()->puede('restaurantes.gestionar'))
            <a href="{{ route('restaurantes.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo restaurante</a>
        @endif
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Restaurante</th><th>NIT</th><th>Casa matriz</th><th class="num">Sucursales</th><th class="num">Usuarios</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($restaurantes as $r)
                    <tr>
                        <td class="font-semibold">{{ $r->nombre }}<span class="block text-xs font-normal text-tenue">{{ $r->propietario }}</span></td>
                        <td>{{ $r->nit ?: '—' }}</td>
                        <td>{{ $r->matriz?->nombre ?? '—' }}</td>
                        <td class="num">{{ $r->sucursales_count }}</td>
                        <td class="num">{{ $r->usuarios_count }}</td>
                        <td><x-estado :valor="$r->estado" /></td>
                        <td class="whitespace-nowrap"><a href="{{ route('restaurantes.show', $r) }}" class="btn-secundario btn-chico"><i class="fa fa-eye"></i> Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-tenue">No hay restaurantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $restaurantes->links() }}</div>
@endsection
