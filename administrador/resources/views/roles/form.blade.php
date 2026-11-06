@extends('layout')

@section('titulo', $rol->exists ? 'Editar rol' : 'Nuevo rol')

@section('contenido')
    <form method="POST" action="{{ $rol->exists ? route('roles.update', $rol) : route('roles.store') }}" class="max-w-3xl space-y-4">
        @csrf
        @if ($rol->exists) @method('PUT') @endif

        <section class="tarjeta p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                @if ($rol->protegido)
                    <div><span class="etiqueta">Nombre</span><p class="font-semibold">{{ $rol->nombre }}</p></div>
                @else
                    <x-campo nombre="nombre" etiqueta="Nombre" :valor="$rol->nombre" requerido />
                @endif
                <div>
                    <span class="etiqueta">Ámbito</span>
                    <p class="font-semibold">{{ \App\Models\Rol::AMBITOS[$rol->ambito] }}</p>
                    @unless ($rol->exists)<input type="hidden" name="ambito" value="{{ $rol->ambito }}">@endunless
                </div>
                <x-campo nombre="descripcion" etiqueta="Descripción" :valor="$rol->descripcion" class="sm:col-span-2" />
            </div>
        </section>

        <section class="tarjeta p-6">
            <h2 class="mb-1 font-bold">Permisos</h2>
            @if ($rol->esTotal())
                <p class="mb-3 text-sm text-tenue">Este rol tiene siempre todos los permisos de su ámbito.</p>
            @endif
            @php $marcados = old('permisos', $rol->exists ? $rol->permisos->pluck('id')->all() : []); @endphp
            @foreach (($permisos[$rol->ambito] ?? collect())->groupBy('modulo') as $modulo => $lista)
                <p class="mt-4 mb-1 text-xs font-semibold tracking-wide text-tenue uppercase">{{ $modulo }}</p>
                <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2">
                    @foreach ($lista as $permiso)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}" class="h-4 w-4 accent-marca-500" @checked(in_array($permiso->id, $marcados)) @disabled($rol->esTotal())>
                            {{ $permiso->nombre }}
                        </label>
                    @endforeach
                </div>
            @endforeach
        </section>

        <div class="flex gap-2">
            <button class="btn-primario"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('roles.index') }}" class="btn-secundario">Volver</a>
        </div>
    </form>
@endsection
