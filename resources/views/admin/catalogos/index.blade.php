@extends('admin.layout')

@section('titulo', $def['titulo'])

@section('contenido')
    @if (! empty($def['descripcion']))
        <p class="mb-4 max-w-3xl text-sm text-tenue">{{ $def['descripcion'] }}</p>
    @endif

    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Escriba para buscar...">
        </div>
        @if ($def['sucursal'] ?? false)
            <div class="w-full sm:w-56">
                <label class="etiqueta" for="sucursal">Sucursal</label>
                <select name="sucursal" id="sucursal" class="campo" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    @foreach ($sucursales as $id => $nombre)
                        <option value="{{ $id }}" @selected(request('sucursal') == $id)>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        <a href="{{ route('admin.catalogos.create', [$def['slug'], 'sucursal' => request('sucursal')]) }}" class="btn-primario ml-auto">
            <i class="fa fa-plus"></i> Nuevo {{ $def['singular'] }}
        </a>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    @foreach ($def['columnas'] as [$etiqueta, $formato])
                        <th @class(['text-right' => in_array($formato, ['numero', 'moneda'])])>{{ $etiqueta }}</th>
                    @endforeach
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $registro)
                    <tr>
                        @foreach ($def['columnas'] as $campo => $columna)
                            @php $valor = isset($columna[2]) ? $columna[2]($registro) : $registro->{$campo}; @endphp
                            @switch($columna[1])
                                @case('estado')
                                    <td><x-admin.estado :valor="$valor" /></td>
                                    @break
                                @case('color')
                                    <td><span class="inline-flex items-center gap-2"><span class="h-4 w-4 rounded-full ring-1 ring-black/10" style="background: {{ $valor ?: '#fff' }}"></span><span class="text-xs text-tenue">{{ $valor }}</span></span></td>
                                    @break
                                @case('moneda')
                                    <td class="num">{{ $valor !== null ? number_format((float) $valor, 2) : '—' }}</td>
                                    @break
                                @case('numero')
                                    <td class="num">{{ $valor !== null ? rtrim(rtrim(number_format((float) $valor, 3), '0'), '.') : '—' }}</td>
                                    @break
                                @default
                                    <td>{{ $valor }}</td>
                            @endswitch
                        @endforeach
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.catalogos.edit', [$def['slug'], $registro->id]) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                            <form method="POST" action="{{ route('admin.catalogos.destroy', [$def['slug'], $registro->id]) }}" class="inline"
                                  onsubmit="return confirm('¿{{ str_starts_with($def['eliminar'], 'estado:') ? 'Dar de baja' : 'Eliminar' }} este registro?')">
                                @csrf @method('DELETE')
                                <button class="btn-peligro btn-chico" title="{{ str_starts_with($def['eliminar'], 'estado:') ? 'Dar de baja' : 'Eliminar' }}"><i class="fa fa-trash-o"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($def['columnas']) + 1 }}" class="py-10 text-center text-tenue">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $registros->links() }}</div>
@endsection
