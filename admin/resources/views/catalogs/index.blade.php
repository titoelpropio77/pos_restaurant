@extends('layout')

@section('title', $def['title'])

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field">
        </div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @can("catalogs.{$def['slug']}.create")
        <a href="{{ route('catalogs.create', $def['slug']) }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo</a>
        @endcan
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead><tr>@foreach ($def['columns'] as $title)<th>{{ $title }}</th>@endforeach<th class="w-1"></th></tr></thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        @foreach ($def['columns'] as $field => $title)
                            <td>
                                @if ($field === 'status')
                                    <x-status :value="$record->status" />
                                @else
                                    {{ data_get($record, $field) ?? '—' }}
                                @endif
                            </td>
                        @endforeach
                        <td class="whitespace-nowrap">
                            @can("catalogs.{$def['slug']}.update")
                            <a href="{{ route('catalogs.edit', [$def['slug'], $record->id]) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @can("catalogs.{$def['slug']}.delete")
                            <form method="POST" action="{{ route('catalogs.destroy', [$def['slug'], $record->id]) }}" class="inline" onsubmit="return confirm('¿Eliminar este registro?')">
                                @csrf @method('DELETE')
                                <button class="btn-danger btn-small"><i class="fa fa-trash"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['columns']) + 1 }}" class="py-8 text-center text-muted">Sin registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
@endsection
