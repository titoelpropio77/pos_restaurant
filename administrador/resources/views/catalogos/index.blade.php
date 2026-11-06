@extends('layout')

@section('titulo', $def['titulo'])

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo">
        </div>
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        <a href="{{ route('catalogos.create', $def['slug']) }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo</a>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr>@foreach ($def['columnas'] as $titulo)<th>{{ $titulo }}</th>@endforeach<th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        @foreach ($def['columnas'] as $campo => $titulo)
                            <td>
                                @if ($campo === 'estado')
                                    <x-estado :valor="$registro->estado" />
                                @else
                                    {{ data_get($registro, $campo) ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="whitespace-nowrap">
                            <a href="{{ route('catalogos.edit', [$def['slug'], $registro->id]) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                            <form method="POST" action="{{ route('catalogos.destroy', [$def['slug'], $registro->id]) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?')">
                                @csrf @method('DELETE')
                                <button class="btn-peligro btn-chico"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['columnas']) + 1 }}" class="py-8 text-center text-tenue">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registros->links() }}</div>
@endsection
