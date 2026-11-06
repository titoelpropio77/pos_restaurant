@extends('layout')

@section('titulo', 'Permisos')

@section('contenido')
    <div class="mb-4 flex"><a href="{{ route('permisos.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo permiso</a></div>

    @foreach (\App\Models\Rol::AMBITOS as $ambito => $titulo)
        <section class="tarjeta mb-4 overflow-x-auto">
            <h2 class="px-5 pt-5 pb-3 font-bold">{{ $titulo }}</h2>
            <table class="tabla">
                <thead><tr><th>Clave</th><th>Permiso</th><th>Módulo</th><th class="num">Roles</th><th class="w-1"></th></tr></thead>
                <tbody>
                    @foreach ($permisos[$ambito] ?? [] as $p)
                        <tr>
                            <td class="font-mono text-xs">{{ $p->clave }} @if ($p->protegido)<i class="fa fa-lock text-tenue" title="Lo usa el sistema"></i>@endif</td>
                            <td>{{ $p->nombre }}</td>
                            <td>{{ $p->modulo }}</td>
                            <td class="num">{{ $p->roles_count }}</td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('permisos.edit', $p) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                                @unless ($p->protegido)
                                    <form method="POST" action="{{ route('permisos.destroy', $p) }}" class="inline" onsubmit="return confirm('¿Eliminar este permiso?')">
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
