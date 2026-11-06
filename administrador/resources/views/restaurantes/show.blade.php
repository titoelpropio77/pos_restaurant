@extends('layout')

@section('titulo', $restaurante->nombre)

@section('contenido')
    @php $gestiona = auth()->user()->puede('restaurantes.gestionar'); @endphp

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <x-estado :valor="$restaurante->estado" />
        @if ($gestiona)
            <div class="ml-auto flex gap-2">
                <a href="{{ route('restaurantes.edit', $restaurante) }}" class="btn-secundario"><i class="fa fa-pencil"></i> Editar</a>
                <form method="POST" action="{{ route('restaurantes.estado', $restaurante) }}"
                      onsubmit="return confirm('{{ $restaurante->estado === 'ACTIVO' ? '¿Dar de baja? Sus usuarios perderán el acceso de inmediato.' : '¿Reactivar el restaurante?' }}')">
                    @csrf
                    <button @class(['btn-peligro' => $restaurante->estado === 'ACTIVO', 'btn-primario' => $restaurante->estado !== 'ACTIVO'])>
                        <i class="fa fa-power-off"></i> {{ $restaurante->estado === 'ACTIVO' ? 'Dar de baja' : 'Reactivar' }}
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <section class="tarjeta p-5">
            <h2 class="mb-3 font-bold">Datos</h2>
            <dl class="space-y-2 text-sm">
                @foreach (['NIT' => $restaurante->nit, 'Propietario' => $restaurante->propietario, 'Correo' => $restaurante->correo, 'Teléfono' => $restaurante->telefono, 'Dirección' => $restaurante->direccion, 'Actividad' => $restaurante->actividad] as $k => $v)
                    <div class="flex justify-between gap-3"><dt class="text-tenue">{{ $k }}</dt><dd class="text-right font-medium">{{ $v ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </section>

        <section class="tarjeta overflow-x-auto xl:col-span-2">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Sucursales</h2>
                @if ($gestiona)
                    <a href="{{ route('sucursales.create', ['restaurante' => $restaurante->id]) }}" class="btn-secundario btn-chico"><i class="fa fa-plus"></i> Sucursal</a>
                @endif
            </div>
            <table class="tabla">
                <thead><tr><th>Sucursal</th><th>Tipo</th><th>Estado</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($restaurante->sucursales as $s)
                        @include('sucursales.fila', ['s' => $s, 'conRestaurante' => false])
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="tarjeta overflow-x-auto xl:col-span-3">
            <div class="flex items-center justify-between px-5 pt-5 pb-3">
                <h2 class="font-bold">Usuarios</h2>
                @if (auth()->user()->puede('usuarios.gestionar'))
                    <a href="{{ route('usuarios.create', ['restaurante' => $restaurante->id]) }}" class="btn-secundario btn-chico"><i class="fa fa-plus"></i> Usuario</a>
                @endif
            </div>
            <table class="tabla">
                <thead><tr><th>Usuario</th><th>Nombre</th><th>Rol</th><th>Estado</th></tr></thead>
                <tbody>
                    @forelse ($restaurante->usuarios as $u)
                        <tr><td class="font-semibold">{{ $u->usuario }}</td><td>{{ $u->nombres }}</td><td>{{ $u->rol?->nombre ?? '—' }}</td><td><x-estado :valor="$u->estado" /></td></tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-center text-tenue">Sin usuarios.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
