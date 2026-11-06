@extends('layout')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cifras as [$texto, $valor, $icono])
            <div class="tarjeta p-5">
                <div class="flex items-center justify-between text-sm text-tenue">{{ $texto }} <i class="fa {{ $icono }}"></i></div>
                <p class="mt-2 font-display text-3xl font-bold text-panel tabular-nums">{{ number_format($valor) }}</p>
            </div>
        @endforeach
    </div>

    <section class="tarjeta overflow-x-auto">
        <div class="flex items-center justify-between px-5 pt-5 pb-3">
            <h2 class="font-bold">Restaurantes</h2>
            <span class="text-sm text-tenue">Ventas de los últimos 30 días</span>
        </div>
        <table class="tabla">
            <thead><tr><th>Restaurante</th><th class="num">Sucursales</th><th class="num">Usuarios</th><th class="num">Ventas</th><th class="num">Total Bs</th><th>Estado</th></tr></thead>
            <tbody>
                @forelse ($restaurantes as $r)
                    <tr>
                        <td class="font-semibold">
                            @if (auth()->user()->puede('restaurantes.ver'))
                                <a href="{{ route('restaurantes.show', $r) }}" class="text-marca-600 hover:underline">{{ $r->nombre }}</a>
                            @else
                                {{ $r->nombre }}
                            @endif
                        </td>
                        <td class="num">{{ $r->sucursales_count }}</td>
                        <td class="num">{{ $r->usuarios_count }}</td>
                        <td class="num">{{ number_format($ventas[$r->id]->cantidad ?? 0) }}</td>
                        <td class="num">{{ number_format((float) ($ventas[$r->id]->total ?? 0), 2) }}</td>
                        <td><x-estado :valor="$r->estado" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-tenue">Todavía no hay restaurantes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
