@extends('admin.layout')

@section('title', 'Productos')

@section('content')
    <form class="mb-4 flex flex-wrap items-end gap-3" method="GET">
        <div class="w-full sm:w-72">
            <label class="label" for="q">Buscar</label>
            <input type="search" name="q" id="q" value="{{ request('q') }}" class="field" placeholder="Nombre o código de barras">
        </div>
        <div class="w-full sm:w-52">
            <label class="label" for="category">Categoría</label>
            <select name="category" id="category" class="field" onchange="this.form.submit()">
                <option value="">Todas</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}" @selected(request('category') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label" for="status">Estado</label>
            <select name="status" id="status" class="field" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Activos</option>
                <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Inactivos</option>
            </select>
        </div>
        <button class="btn-secondary"><i class="fa fa-search"></i> Buscar</button>
        @can('products.create')
        <a href="{{ route('admin.products.create') }}" class="btn-primary ml-auto"><i class="fa fa-plus"></i> Nuevo producto</a>
        @endcan
    </form>

    <div class="card overflow-x-auto">
        <table class="data-table">
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
                @forelse ($products as $product)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($product->image_path)
                                    <img src="{{ asset($product->image_path) }}" class="h-9 w-9 rounded-lg object-cover" alt="">
                                @else
                                    <span class="h-9 w-9 shrink-0 rounded-lg" style="background: {{ $product->color ?: '#337ab7' }}"></span>
                                @endif
                                <div>
                                    <p class="font-semibold">{{ $product->product_name }}</p>
                                    <p class="text-xs text-muted">
                                        @if ($product->barcode)<i class="fa fa-barcode"></i> {{ $product->barcode }}@endif
                                        @if ($product->options_mode)<span class="badge bg-blue-50 text-blue-700">opciones</span>@endif
                                        @if ($product->timed === 'T')<span class="badge bg-purple-50 text-purple-700">por tiempo</span>@endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->category?->group_name ?? '—' }}</td>
                        <td class="num font-semibold">{{ number_format((float) $product->sale_price, 2) }}</td>
                        <td>
                            <span @class(['badge', 'bg-green-100 text-green-800' => $product->branches_count > 0, 'bg-amber-100 text-amber-800' => $product->branches_count === 0])>
                                {{ $product->branches_count }} de {{ $totalBranches }}
                            </span>
                        </td>
                        <td>{{ $product->recipe_count ? $product->recipe_count.' insumo(s)' : '—' }}</td>
                        <td><x-admin.status :value="$product->status" /></td>
                        <td class="whitespace-nowrap text-right">
                            @can('products.update')
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn-secondary btn-small"><i class="fa fa-pencil"></i> Editar</a>
                            @endcan
                            @if ($product->status === 'ACTIVE')
                                @can('products.delete')
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline" onsubmit="return confirm('¿Desactivar este producto? Dejará de aparecer en el POS.')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger btn-small" title="Desactivar"><i class="fa fa-ban"></i></button>
                                </form>
                                @endcan
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-muted">No hay productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
