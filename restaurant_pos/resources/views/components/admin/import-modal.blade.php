{{--
    Modal de carga masiva desde Excel/CSV. Se abre con $dispatch('open-import') y se reabre solo si la importación
    devolvió errores en el campo "file".
    columns: [encabezado => explicación]
--}}
@props(['title', 'singular', 'columns', 'action', 'template'])

<div x-data="{ open: {{ $errors->has('file') ? 'true' : 'false' }}, name: '' }" @open-import.window="open = true"
     @keydown.escape.window="open = false" x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="import-title">
    <div class="card max-h-full w-full max-w-2xl overflow-y-auto p-5" @click.outside="open = false">
        <div class="mb-3 flex items-center">
            <h2 id="import-title" class="text-lg font-bold">{{ $title }}</h2>
            <button type="button" class="ml-auto text-muted" @click="open = false" title="Cerrar"><i class="fa fa-times"></i></button>
        </div>

        <p class="mb-3 text-sm text-muted">
            Suba un archivo <strong>Excel</strong> (.xlsx, .xls), .ods o .csv con una fila por {{ $singular }} y estas columnas en la primera fila:
        </p>
        <table class="data-table mb-3 text-sm">
            <thead><tr><th>Columna</th><th>Contenido</th></tr></thead>
            <tbody>
                @foreach ($columns as $header => $help)
                    <tr><td class="whitespace-nowrap font-mono">{{ $header }}</td><td>{{ $help }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <p class="mb-4 text-sm text-muted">
            Máximo {{ \App\Support\SpreadsheetFile::MAX_ROWS }} filas. Si alguna fila tiene errores no se guarda ninguna.
            <a href="{{ $template }}" class="font-semibold text-brand-600 underline"><i class="fa fa-file-excel-o"></i> Descargar plantilla Excel</a>
        </p>

        <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            <label class="label" for="import-file">Archivo</label>
            <input type="file" name="file" id="import-file" required class="field"
                   accept="{{ collect(\App\Support\SpreadsheetFile::EXTENSIONS)->map(fn ($e) => '.'.$e)->implode(',') }}"
                   @change="name = $event.target.files[0]?.name || ''">
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
