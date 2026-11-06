@extends('layout')

@section('titulo', 'Cuentas del sistema')

@section('contenido')
    <div class="mb-4 flex"><a href="{{ route('administradores.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nueva cuenta</a></div>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Último ingreso</th><th>Estado</th><th class="w-1"></th></tr></thead>
            <tbody>
                @foreach ($cuentas as $c)
                    <tr>
                        <td class="font-semibold">{{ $c->nombre }}</td>
                        <td>{{ $c->correo }}</td>
                        <td>{{ $c->rol->nombre }}</td>
                        <td>{{ $c->ultimo_ingreso?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><x-estado :valor="$c->estado" /></td>
                        <td class="whitespace-nowrap">
                            <a href="{{ route('administradores.edit', $c) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                            @if ($c->estaActivo())
                                <form method="POST" action="{{ route('administradores.destroy', $c) }}" class="inline" onsubmit="return confirm('¿Dar de baja esta cuenta?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-peligro btn-chico"><i class="fa fa-ban"></i> Baja</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $cuentas->links() }}</div>
@endsection
