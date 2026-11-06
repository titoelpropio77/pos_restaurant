@extends('layout')

@section('titulo', 'Roles')

@section('contenido')
    <div class="mb-4 flex justify-end gap-2">
        <a href="{{ route('roles.create', ['ambito' => 'RESTAURANTE']) }}" class="btn-primario"><i class="fa fa-plus"></i> Rol de restaurante</a>
        <a href="{{ route('roles.create', ['ambito' => 'SISTEMA']) }}" class="btn-secundario"><i class="fa fa-plus"></i> Rol del sistema</a>
    </div>

    @foreach (\App\Models\Rol::AMBITOS as $ambito => $titulo)
        <section class="tarjeta mb-4 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $titulo }}</h2>
            <table class="tabla">
                <thead><tr><th>Rol</th><th class="num">Permisos</th><th class="num">Cuentas</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($roles[$ambito] ?? [] as $rol)
                        <tr>
                            <td class="font-semibold">{{ $rol->nombre }}
                                @if ($rol->protegido)<i class="fa fa-lock text-xs text-tenue" title="Rol del sistema"></i>@endif
                                <span class="block text-xs font-normal text-tenue">{{ $rol->descripcion }}</span>
                            </td>
                            <td class="num">{{ $rol->permisos_count }}</td>
                            <td class="num">{{ $rol->administradores_count + $rol->usuarios_count }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('roles.edit', $rol) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                                @unless ($rol->protegido)
                                    <form method="POST" action="{{ route('roles.destroy', $rol) }}" class="inline" onsubmit="return confirm('¿Eliminar este rol?')">
                                        @csrf @method('DELETE')
                                        <button class="btn-peligro btn-chico"><i class="fa fa-trash"></i></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endforeach
@endsection
