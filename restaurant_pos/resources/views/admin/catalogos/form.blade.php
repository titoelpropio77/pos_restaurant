@extends('admin.layout')

@section('titulo', ($registro->exists ? 'Editar ' : 'Nuevo ').$def['singular'])

@section('contenido')
    <form method="POST" class="tarjeta max-w-4xl p-6"
          action="{{ $registro->exists ? route('admin.catalogos.update', [$def['slug'], $registro->id]) : route('admin.catalogos.store', $def['slug']) }}">
        @csrf
        @if ($registro->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-4">
            @foreach ($def['campos'] as $nombre => $campo)
                @php $opciones = isset($campo[3]) ? (is_callable($campo[3]) ? $campo[3]() : $campo[3]) : []; @endphp
                <x-admin.campo :nombre="$nombre" :etiqueta="$campo[0]" :tipo="$campo[1]"
                               :valor="$registro->{$nombre} instanceof \DateTimeInterface ? $registro->{$nombre}->format('Y-m-d') : $registro->{$nombre}"
                               :opciones="$opciones" :requerido="str_contains($campo[2], 'required')"
                               :vacio="$campo['vacio'] ?? null" :ayuda="$campo['ayuda'] ?? null"
                               @class([$campo['ancho'] ?? 'sm:col-span-1']) />
            @endforeach
        </div>

        <div class="mt-6 flex gap-2 border-t border-borde pt-4">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('admin.catalogos.index', $def['slug']) }}" class="btn-secundario">Cancelar</a>
        </div>
    </form>
@endsection
