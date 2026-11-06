@extends('layout')

@section('titulo', 'Auditoría')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Descripción">
        </div>
        <x-campo nombre="accion" etiqueta="Acción" tipo="select" :opciones="$acciones->combine($acciones)" :valor="request('accion')" vacio="Todas" />
        <x-campo nombre="entidad" etiqueta="Módulo" tipo="select" :opciones="$entidades->combine($entidades)" :valor="request('entidad')" vacio="Todos" />
        <button class="btn-secundario"><i class="fa fa-search"></i> Filtrar</button>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Cuenta</th><th>Acción</th><th>Descripción</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($registros as $r)
                    <tr>
                        <td class="whitespace-nowrap tabular-nums">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->administrador?->nombre ?? '—' }}</td>
                        <td><span class="insignia bg-slate-100 text-slate-700">{{ $r->accion }}</span></td>
                        <td>{{ $r->descripcion }}</td>
                        <td class="text-xs text-tenue">{{ $r->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-tenue">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registros->links() }}</div>
@endsection
