@extends('layout')

@section('title', 'Auditoría')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Descripción">
        </div>
        <x-field name="action" label="Acción" type="select" :options="$actions->combine($actions)" :value="request('action')" empty="Todas" />
        <x-field name="entity" label="Módulo" type="select" :options="$entities->combine($entities)" :value="request('entity')" empty="Todos" />
        <button class="btn-secondary"><i class="fa fa-search"></i> Filtrar</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Fecha</th><th>Cuenta</th><th>Acción</th><th>Descripción</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($records as $r)
                    <tr>
                        <td class="whitespace-nowrap tabular-nums">{{ $r->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $r->admin?->name ?? '—' }}</td>
                        <td><span class="badge bg-slate-100 text-slate-700">{{ $r->action }}</span></td>
                        <td>{{ $r->description }}</td>
                        <td class="text-xs text-muted">{{ $r->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-muted">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
@endsection
