<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Administración') · POS Gourmet</title>
    <link rel="icon" href="{{ asset('abms1/imagenes/logo.png') }}">
    <link rel="stylesheet" href="{{ asset('abms1/css/puglins/font-awesome.css') }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="bg-fondo text-tinta antialiased" x-data="{ menu: false }">
@php
    $menu = [
        'General' => [
            ['admin.dashboard', 'fa-tachometer', 'Inicio', 'admin.dashboard'],
            ['admin.reportes.index', 'fa-bar-chart', 'Reportes', 'admin.reportes.*'],
        ],
        'Menú y ventas' => [
            ['admin.productos.index', 'fa-cutlery', 'Productos', 'admin.productos.*'],
            [['admin.catalogos.index', 'categorias'], 'fa-th-large', 'Categorías', 'categorias'],
            [['admin.catalogos.index', 'grupos'], 'fa-object-group', 'Grupos de reporte', 'grupos'],
            [['admin.catalogos.index', 'clientes'], 'fa-address-book-o', 'Clientes', 'clientes'],
        ],
        'Salón y personal' => [
            [['admin.catalogos.index', 'mesas'], 'fa-square-o', 'Mesas', 'mesas'],
            [['admin.catalogos.index', 'personal'], 'fa-id-badge', 'Meseros / personal', 'personal'],
            ['admin.usuarios.index', 'fa-users', 'Usuarios', 'admin.usuarios.*'],
        ],
        'Inventario' => [
            ['admin.inventario.stock', 'fa-cubes', 'Stock', 'admin.inventario.stock'],
            ['admin.inventario.movimientos', 'fa-exchange', 'Movimientos', 'admin.inventario.movimientos|admin.inventario.create|admin.inventario.show'],
            ['admin.inventario.kardex', 'fa-list-alt', 'Kardex', 'admin.inventario.kardex'],
            [['admin.catalogos.index', 'insumos'], 'fa-lemon-o', 'Insumos', 'insumos'],
            [['admin.catalogos.index', 'almacenes'], 'fa-archive', 'Almacenes', 'almacenes'],
            [['admin.catalogos.index', 'proveedores'], 'fa-truck', 'Proveedores', 'proveedores'],
            [['admin.catalogos.index', 'unidades'], 'fa-balance-scale', 'Unidades de medida', 'unidades'],
            [['admin.catalogos.index', 'motivos'], 'fa-tags', 'Motivos de movimiento', 'motivos'],
        ],
        'Empresa' => [
            [['admin.catalogos.index', 'sucursales'], 'fa-building-o', 'Sucursales', 'sucursales'],
            ['admin.facturacion.index', 'fa-file-text-o', 'Facturación SIN', 'admin.facturacion.*'],
        ],
    ];
    $activo = function ($patron) {
        if (! str_starts_with($patron, 'admin.')) {
            return request()->route('catalogo') === $patron;
        }
        return collect(explode('|', $patron))->contains(fn ($p) => request()->routeIs($p));
    };
@endphp

<div class="flex min-h-screen">
    {{-- Menú lateral --}}
    <aside class="no-imprimir fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-panel text-slate-300 transition lg:translate-x-0"
           :class="menu && 'translate-x-0'">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 px-5 py-4 text-lg text-white">
            <img src="{{ asset('abms1/imagenes/logo.png') }}" class="h-8 w-8 object-contain" alt="">
            <span>POS<strong class="text-marca-500">Gourmet</strong></span>
        </a>
        <nav class="px-3 pb-6">
            @foreach ($menu as $grupo => $items)
                <p class="mt-4 mb-1 px-3 text-[11px] font-semibold tracking-wider text-slate-500 uppercase">{{ $grupo }}</p>
                @foreach ($items as [$ruta, $icono, $texto, $patron])
                    @php $url = is_array($ruta) ? route($ruta[0], $ruta[1]) : route($ruta); @endphp
                    <a href="{{ $url }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-marca-500 text-white' => $activo($patron),
                        'hover:bg-panel-2 hover:text-white' => ! $activo($patron),
                    ])>
                        <i class="fa {{ $icono }} w-4 text-center"></i>{{ $texto }}
                    </a>
                @endforeach
            @endforeach
        </nav>
    </aside>
    <div class="fixed inset-0 z-30 bg-black/40 lg:hidden" x-show="menu" x-cloak @click="menu = false"></div>

    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="no-imprimir sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-borde bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="btn-secundario btn-chico lg:hidden" @click="menu = true"><i class="fa fa-bars"></i></button>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-bold">@yield('titulo', 'Administración')</h1>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('pos.index') }}" class="btn-primario"><i class="fa fa-desktop"></i><span class="hidden sm:inline">Ir al POS</span></a>
                <span class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold sm:flex"><i class="fa fa-user text-marca-500"></i>{{ auth()->user()->nombres }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secundario" title="Salir"><i class="fa fa-sign-out"></i></button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('ok'))
                <div class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" x-data="{ v: true }" x-show="v">
                    <i class="fa fa-check-circle"></i>{{ session('ok') }}
                    <button class="ml-auto" @click="v = false"><i class="fa fa-times"></i></button>
                </div>
            @endif
            @if (session('error') || $errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold"><i class="fa fa-exclamation-circle"></i> {{ session('error') ?? 'Revise los datos del formulario:' }}</p>
                    @if ($errors->any())
                        <ul class="mt-1 list-inside list-disc">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
