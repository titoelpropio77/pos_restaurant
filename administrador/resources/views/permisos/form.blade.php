@extends('layout')

@section('titulo', $permiso->exists ? 'Editar permiso' : 'Nuevo permiso')

@section('contenido')
    <form method="POST" action="{{ $permiso->exists ? route('permisos.update', $permiso) : route('permisos.store') }}" class="tarjeta max-w-2xl p-6">
        @csrf
        @if ($permiso->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @if ($permiso->protegido)
                <div><span class="etiqueta">Clave</span><p class="font-mono text-sm">{{ $permiso->clave }}</p></div>
            @else
                <x-campo nombre="clave" etiqueta="Clave" :valor="$permiso->clave" requerido ayuda="Formato modulo.accion, por ejemplo panel.descuentos." />
            @endif
            @if ($permiso->exists)
                <div><span class="etiqueta">Ámbito</span><p class="font-semibold">{{ \App\Models\Rol::AMBITOS[$permiso->ambito] }}</p></div>
            @else
                <x-campo nombre="ambito" etiqueta="Ámbito" tipo="select" :opciones="\App\Models\Rol::AMBITOS" :valor="$permiso->ambito" requerido />
            @endif
            <x-campo nombre="nombre" etiqueta="Nombre" :valor="$permiso->nombre" requerido />
            <x-campo nombre="modulo" etiqueta="Módulo" :valor="$permiso->modulo" requerido />
        </div>

        @unless ($permiso->protegido)
            <p class="mt-4 text-xs text-tenue">Un permiso nuevo no restringe nada hasta que una ruta de las aplicaciones lo exija.</p>
        @endunless

        <div class="mt-5 flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('permisos.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
