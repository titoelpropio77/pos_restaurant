@extends('admin.layout')

@section('titulo', 'Usuarios')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Nombre o usuario">
        </div>
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        <a href="{{ route('admin.usuarios.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo usuario</a>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr><th>Usuario</th><th>Nombre</th><th>Nivel</th><th>Sucursal</th><th>Puede vender</th><th>Estado</th><th class="w-1"></th></tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $u)
                    <tr>
                        <td class="font-semibold">{{ $u->usuario }}</td>
                        <td>{{ $u->nombres }} <span class="block text-xs text-tenue">{{ $u->cargo }}</span></td>
                        <td>
                            <span @class(['insignia', 'bg-marca-100 text-marca-700' => $u->esAdministrador(), 'bg-slate-100 text-slate-700' => ! $u->esAdministrador()])>
                                {{ $u->esAdministrador() ? 'Administrador' : 'Cajero' }}
                            </span>
                        </td>
                        <td>{{ $u->puntoVenta?->sucursal?->nombre ?? '—' }} @if ($u->puntoVenta?->venta_multialmacen)<span class="text-xs text-tenue">(multi-sucursal)</span>@endif</td>
                        <td>{!! $u->puntoVenta?->puede_vender ? '<i class="fa fa-check text-green-600"></i>' : '<i class="fa fa-times text-red-500"></i>' !!}</td>
                        <td><x-admin.estado :valor="$u->estado" /></td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.usuarios.edit', $u) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                            @if ($u->estado === 'ACTIVO' && ! $u->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.usuarios.destroy', $u) }}" class="inline" onsubmit="return confirm('¿Dar de baja a este usuario?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-peligro btn-chico" title="Dar de baja"><i class="fa fa-ban"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-tenue">No hay usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $usuarios->links() }}</div>
@endsection
