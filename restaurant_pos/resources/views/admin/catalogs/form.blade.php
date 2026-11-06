@extends('admin.layout')

@section('title', ($record->exists ? 'Editar' : \App\Admin\Catalogs::agree($def, 'Nuevo')).' '.$def['singular'])

@section('content')
    <form method="POST" class="card max-w-4xl p-6"
          action="{{ $record->exists ? route('admin.catalogs.update', [$def['slug'], $record->id]) : route('admin.catalogs.store', $def['slug']) }}">
        @csrf
        @if ($record->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-4">
            @foreach ($def['fields'] as $name => $field)
                @php $options = isset($field[3]) ? (is_callable($field[3]) ? $field[3]() : $field[3]) : []; @endphp
                <x-admin.field :name="$name" :label="$field[0]" :type="$field[1]"
                               :value="$record->{$name} instanceof \DateTimeInterface ? $record->{$name}->format('Y-m-d') : $record->{$name}"
                               :options="$options" :required="str_contains($field[2], 'required')"
                               :empty="$field['empty'] ?? null" :help="$field['help'] ?? null"
                               @class([$field['width'] ?? 'sm:col-span-1']) />
            @endforeach
        </div>

        <div class="mt-6 flex gap-2 border-t border-line pt-4">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('admin.catalogs.index', $def['slug']) }}" class="btn-secondary">Cancelar</a>
        </div>
    </form>
@endsection
