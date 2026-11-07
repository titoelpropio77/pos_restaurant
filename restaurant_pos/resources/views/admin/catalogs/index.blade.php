@extends('admin.layout')

@section('title', $def['title'])

@section('content')
    @if (! empty($def['description']))
        <p class="mb-4 max-w-3xl text-sm text-muted">{{ $def['description'] }}</p>
    @endif

    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Escriba para buscar...">
        </div>
        @if ($def['branch'] ?? false)
            <div class="w-full sm:w-56">
                <label class="label" for="branch">Sucursal</label>
                <select name="branch" id="branch" class="field" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($branches as $id => $name)
                        <option value="{{ $id }}" @selected(request('branch') == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @can("catalogs.{$def['slug']}.create")
            <div class="ml-auto flex flex-wrap gap-2">
                @isset($def['import'])
                    <button type="button" class="btn-secondary" x-data @click="$dispatch('open-import')">
                        <i class="fa fa-upload"></i> Carga masiva
                    </button>
                @endisset
                <a href="{{ route('admin.catalogs.create', [$def['slug'], 'branch' => request('branch')]) }}" class="btn-primary">
                    <i class="fa fa-plus"></i> {{ \App\Admin\Catalogs::agree($def, 'Nuevo') }} {{ $def['singular'] }}
                </a>
            </div>
        @endcan
    </form>

    @isset($def['import'])
        @can("catalogs.{$def['slug']}.create")
            <div x-data="{ open: {{ $errors->has('file') ? 'true' : 'false' }}, name: '' }" @open-import.window="open = true"
                 @keydown.escape.window="open = false" x-show="open" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="import-title">
                <div class="card max-h-full w-full max-w-xl overflow-y-auto p-5" @click.outside="open = false">
                    <div class="mb-3 flex items-center">
                        <h2 id="import-title" class="text-lg font-bold">Carga masiva de {{ mb_strtolower($def['title']) }}</h2>
                        <button type="button" class="ml-auto text-muted" @click="open = false" title="Cerrar"><i class="fa fa-times"></i></button>
                    </div>

                    <p class="mb-3 text-sm text-muted">
                        Suba un archivo <strong>Excel</strong> (.xlsx, .xls), .ods o .csv con una fila por {{ $def['singular'] }} y estas columnas en la primera fila:
                    </p>
                    <table class="data-table mb-3 text-sm">
                        <thead><tr><th>Columna</th><th>Contenido</th></tr></thead>
                        <tbody>
                            @foreach ($def['import']['columns'] as $header => $field)
                                <tr><td class="font-mono">{{ $header }}</td><td>{{ $def['import']['help'][$header] ?? $def['fields'][$field][0] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mb-4 text-sm text-muted">
                        Si alguna fila tiene errores no se guarda ninguna.
                        <a href="{{ route('admin.catalogs.import.template', $def['slug']) }}" class="font-semibold text-brand-600 underline"><i class="fa fa-file-excel-o"></i> Descargar plantilla Excel</a>
                    </p>

                    <form method="POST" action="{{ route('admin.catalogs.import', $def['slug']) }}" enctype="multipart/form-data">
                        @csrf
                        <label class="label" for="import-file">Archivo</label>
                        <input type="file" name="file" id="import-file" accept="{{ collect(\App\Admin\CatalogImport::EXTENSIONS)->map(fn ($e) => '.'.$e)->implode(',') }}" required class="field" @change="name = $event.target.files[0]?.name || ''">
                        @error('file')
                            <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                                @foreach ($errors->get('file') as $message)<li>{{ $message }}</li>@endforeach
                            </ul>
                        @enderror
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="btn-secondary" @click="open = false">Cancelar</button>
                            <button class="btn-primary" :disabled="! name"><i class="fa fa-upload"></i> Importar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endisset

    <div class="card overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    @foreach ($def['columns'] as [$label, $format])
                        <th @class(['text-right' => in_array($format, ['number', 'currency'])])>{{ $label }}</th>
                    @endforeach
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        @foreach ($def['columns'] as $field => $column)
                            @php $value = isset($column[2]) ? $column[2]($record) : $record->{$field}; @endphp
                            @switch($column[1])
                                @case('status')
                                    <td><x-admin.status :value="$value" /></td>
                                    @break
                                @case('color')
                                    <td><span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded-full ring-1 ring-black/10" style="background: {{ $value ?: '#fff' }}"></span><span class="text-xs text-muted">{{ $value }}</span></span></td>
                                    @break
                                @case('currency')
                                    <td class="num">{{ $value !== null ? number_format((float) $value, 2) : '—' }}</td>
                                    @break
                                @case('number')
                                    <td class="num">{{ $value !== null ? rtrim(rtrim(number_format((float) $value, 3), '0'), '.') : '—' }}</td>
                                    @break
                                @default
                                    <td>{{ $value }}</td>
                            @endswitch
                        @endforeach
                        <td class="whitespace-nowrap text-right">
                            @can("catalogs.{$def['slug']}.update")
                            <a href="{{ route('admin.catalogs.edit', [$def['slug'], $record->id]) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @can("catalogs.{$def['slug']}.delete")
                            <form method="POST" action="{{ route('admin.catalogs.destroy', [$def['slug'], $record->id]) }}" class="inline"
                                  onsubmit="return confirm('¿{{ str_starts_with($def['delete'], 'status:') ? 'Dar de baja' : 'Eliminar' }} este registro?')">
                                @csrf @method('DELETE')
                                <button class="btn-danger btn-small" title="{{ str_starts_with($def['delete'], 'status:') ? 'Dar de baja' : 'Eliminar' }}"><i class="fa fa-trash-o"></i></button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['columns']) + 1 }}" class="py-10 text-center text-muted">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $records->links() }}</div>
@endsection
