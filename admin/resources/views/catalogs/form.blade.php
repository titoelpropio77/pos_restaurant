@extends('layout')

@section('title', ($record->exists ? 'Editar ' : 'Nuevo: ').$def['singular'])

@section('content')
    <form method="POST" action="{{ $record->exists ? route('catalogs.update', [$def['slug'], $record->id]) : route('catalogs.store', $def['slug']) }}" class="card max-w-xl p-6">
        @csrf
        @if ($record->exists) @method('PUT') @endif

        <div class="grid gap-4">
            @foreach ($def['fields'] as $field => $d)
                @php $options = is_callable($d[3] ?? null) ? $d[3]() : ($d[3] ?? []); @endphp
                <x-field :name="$field" :label="$d[0]" :type="$d[1]" :value="$record->{$field}" :options="$options" :required="str_contains($d[2], 'required')" />
            @endforeach
        </div>

        <div class="mt-5 flex gap-2">
            <button class="btn-primary"><i class="fa fa-check"></i> Guardar</button>
            <a href="{{ route('catalogs.index', $def['slug']) }}" class="btn-secondary">Volver</a>
        </div>
    </form>
@endsection
