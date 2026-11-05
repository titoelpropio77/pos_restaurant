@extends('admin.layout')

@section('titulo', 'Reportes')

@section('contenido')
    <p class="mb-5 text-sm text-tenue">Todos los reportes se pueden filtrar por fechas y sucursal, imprimir y exportar a Excel.</p>
    @foreach ($grupos as $grupo => $reportes)
        <h2 class="mb-3 text-sm font-semibold tracking-wider text-tenue uppercase">{{ $grupo }}</h2>
        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($reportes as $slug => $reporte)
                <a href="{{ route('admin.reportes.show', $slug) }}" class="tarjeta group flex gap-4 p-5 transition hover:ring-marca-500">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-marca-50 text-lg text-marca-600 group-hover:bg-marca-500 group-hover:text-white">
                        <i class="fa {{ $reporte->icono() }}"></i>
                    </span>
                    <span>
                        <span class="block font-bold">{{ $reporte->titulo() }}</span>
                        <span class="mt-1 block text-sm text-tenue">{{ $reporte->descripcion() }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endforeach
@endsection
