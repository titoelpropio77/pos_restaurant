@extends('layout')

@section('titulo', ($registro->exists ? 'Editar ' : 'Nuevo: ').$def['singular'])

@section('contenido')
    <form method="POST" action="{{ $registro->exists ? route('catalogos.update', [$def['slug'], $registro->id]) : route('catalogos.store', $def['slug']) }}" class="tarjeta max-w-xl p-6">
        @csrf
        @if ($registro->exists) @method('PUT') @endif

        <div class="grid gap-4">
            @foreach ($def['campos'] as $campo => $d)
                @php $opciones = is_callable($d[3] ?? null) ? $d[3]() : ($d[3] ?? []); @endphp
                <x-campo :nombre="$campo" :etiqueta="$d[0]" :tipo="$d[1]" :valor="$registro->{$campo}" :opciones="$opciones" :requerido="str_contains($d[2], 'required')" />
            @endforeach
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('catalogos.index', $def['slug']) }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
