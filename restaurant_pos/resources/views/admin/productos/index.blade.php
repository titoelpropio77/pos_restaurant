@extends('admin.layout')

@section('titulo', 'Productos')

@section('contenido')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="etiqueta" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="campo" placeholder="Nombre o código de barras">
        </div>
        <div class="w-full sm:w-52">
            <label class="etiqueta" for="categoria">Categoría</label>
            <select name="categoria" id="categoria" class="campo" onchange="this.form.submit()">
                <option value="">Todas</option>
                @foreach ($categorias as $id => $nombre)
                    <option value="{{ $id }}" @selected(request('categoria') == $id)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="etiqueta" for="estado">Estado</label>
            <select name="estado" id="estado" class="campo" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="ACTIVO" @selected(request('estado') === 'ACTIVO')>Activos</option>
                <option value="INACTIVO" @selected(request('estado') === 'INACTIVO')>Inactivos</option>
            </select>
        </div>
        <button class="btn-secundario"><i class="fa fa-search"></i> Buscar</button>
        <a href="{{ route('admin.productos.create') }}" class="btn-primario ml-auto"><i class="fa fa-plus"></i> Nuevo producto</a>
    </form>

    <div class="tarjeta overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="text-right">Precio (Bs)</th>
                    <th>Sucursales</th>
                    <th>Receta</th>
                    <th>Estado</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($productos as $producto)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($producto->imgnormal)
                                    <img src="{{ asset($producto->imgnormal) }}" class="h-9 w-9 rounded-lg object-cover" alt="">
                                @else
                                    <span class="h-9 w-9 shrink-0 rounded-lg" style="background: {{ $producto->colores ?: '#337ab7' }}"></span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $producto->nom_prod }}</p>
                                    <p class="text-xs text-tenue">
                                        @if ($producto->barcode)<i class="fa fa-barcode"></i> {{ $producto->barcode }}@endif
                                        @if ($producto->presa)<span class="insignia bg-blue-50 text-blue-700">opciones</span>@endif
                                        @if ($producto->tiempo === 'T')<span class="insignia bg-purple-50 text-purple-700">por tiempo</span>@endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $producto->categoria?->nom_grupo ?? '—' }}</td>
                        <td class="num font-semibold">{{ number_format((float) $producto->pre_venta, 2) }}</td>
                        <td>
                            <span @class(['insignia', 'bg-green-100 text-green-800' => $producto->sucursales_count > 0, 'bg-amber-100 text-amber-800' => $producto->sucursales_count === 0])>
                                {{ $producto->sucursales_count }} de {{ $totalSucursales }}
                            </span>
                        </td>
                        <td>{{ $producto->receta_count ? $producto->receta_count.' insumo(s)' : '—' }}</td>
                        <td><x-admin.estado :valor="$producto->estado" /></td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.productos.edit', $producto) }}" class="btn-secundario btn-chico"><i class="fa fa-pencil"></i> Editar</a>
                            @if ($producto->estado === 'ACTIVO')
                                <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="inline" onsubmit="return confirm('¿Desactivar este producto? Dejará de aparecer en el POS.')">
                                    @csrf @method('DELETE')
                                    <button class="btn-peligro btn-chico" title="Desactivar"><i class="fa fa-ban"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-tenue">No hay productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $productos->links() }}</div>
@endsection
