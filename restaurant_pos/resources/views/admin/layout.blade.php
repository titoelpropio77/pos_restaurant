<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administración') · {{ config('app.name') }}</title>
    @include('partials.brand')
    <link rel="stylesheet" href="{{ asset('legacy/css/puglins/font-awesome.css') }}">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="bg-base text-ink antialiased" x-data="{ menu: false }">
@php
    $menu = [
        'General' => [
            ['admin.dashboard', 'fa-tachometer', 'Inicio', 'admin.dashboard', 'panel.access'],
            ['admin.reports.index', 'fa-bar-chart', 'Reportes', 'admin.reports.*', 'reports.view'],
            ['admin.cash_sessions.index', 'fa-money', 'Caja', 'admin.cash_sessions.*', 'cash_sessions.view'],
        ],
        'Menú y ventas' => [
            ['admin.products.index', 'fa-cutlery', 'Productos', 'admin.products.*', 'products.view'],
            [['admin.catalogs.index', 'categories'], 'fa-th-large', 'Categorías', 'categories', 'catalogs.categories.view'],
            [['admin.catalogs.index', 'report_groups'], 'fa-object-group', 'Grupos de reporte', 'report_groups', 'catalogs.report_groups.view'],
            [['admin.catalogs.index', 'customers'], 'fa-address-book-o', 'Clientes', 'customers', 'catalogs.customers.view'],
        ],
        'Salón y personal' => [
            [['admin.catalogs.index', 'dining_tables'], 'fa-square-o', 'Mesas', 'dining_tables', 'catalogs.dining_tables.view'],
            [['admin.catalogs.index', 'staff'], 'fa-id-badge', 'Meseros / personal', 'staff', 'catalogs.staff.view'],
            ['admin.users.index', 'fa-users', 'Usuarios', 'admin.users.*', 'users.view'],
        ],
        'Inventario' => [
            ['admin.inventory.stock', 'fa-cubes', 'Stock', 'admin.inventory.stock', 'inventory.view'],
            ['admin.inventory.movements', 'fa-exchange', 'Movimientos', 'admin.inventory.movements|admin.inventory.create|admin.inventory.show', 'inventory.view'],
            ['admin.inventory.kardex', 'fa-list-alt', 'Kardex', 'admin.inventory.kardex', 'inventory.view'],
            [['admin.catalogs.index', 'supplies'], 'fa-lemon-o', 'Insumos', 'supplies', 'catalogs.supplies.view'],
            [['admin.catalogs.index', 'warehouses'], 'fa-archive', 'Almacenes', 'warehouses', 'catalogs.warehouses.view'],
            [['admin.catalogs.index', 'suppliers'], 'fa-truck', 'Proveedores', 'suppliers', 'catalogs.suppliers.view'],
            [['admin.catalogs.index', 'units'], 'fa-balance-scale', 'Unidades de medida', 'units', 'catalogs.units.view'],
            [['admin.catalogs.index', 'reasons'], 'fa-tags', 'Motivos de movimiento', 'reasons', 'catalogs.reasons.view'],
        ],
        'Empresa' => [
            [['admin.catalogs.index', 'branches'], 'fa-building-o', 'Sucursales', 'branches', 'catalogs.branches.view'],
            ['admin.billing.index', 'fa-file-text-o', 'Facturación SIN', 'admin.billing.*', 'billing.view'],
        ],
    ];
    $active = function ($pattern) {
        if (! str_starts_with($pattern, 'admin.')) {
            return request()->route('catalog') === $pattern;
        }
        return collect(explode('|', $pattern))->contains(fn ($p) => request()->routeIs($p));
    };
@endphp

<div class="flex min-h-screen">
    {{-- Menú lateral --}}
    <aside class="no-print fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-panel text-slate-300 transition lg:translate-x-0"
           :class="menu && 'translate-x-0'">
        <a href="{{ route('admin.dashboard') }}" class="block px-5 py-4">
            <img src="{{ asset('assets/brand/logo-dark.png') }}" class="h-10 w-auto" alt="Mesatek">
        </a>
        <nav class="px-3 pb-6">
            @foreach ($menu as $category_group => $items)
                @php $items = array_filter($items, fn ($item) => auth()->user()->can($item[4])); @endphp
                @continue(! $items)
                <p class="mt-4 mb-1 px-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $category_group }}</p>
                @foreach ($items as [$route, $icon, $text, $pattern, $permission])
                    @php $url = is_array($route) ? route($route[0], $route[1]) : route($route); @endphp
                    <a href="{{ $url }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-brand-500 text-panel' => $active($pattern),
                        'hover:bg-panel-2 hover:text-white' => ! $active($pattern),
                    ])>
                        <i class="fa {{ $icon }} w-4 text-center"></i>{{ $text }}
                    </a>
                @endforeach
            @endforeach
        </nav>
    </aside>
    <div class="fixed inset-0 z-30 bg-black/40 lg:hidden" x-show="menu" x-cloak @click="menu = false"></div>

    <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
        <header class="no-print sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="btn-secondary btn-small lg:hidden" @click="menu = true"><i class="fa fa-bars"></i></button>
            <div class="min-w-0">
                <h1 class="truncate text-lg font-bold">@yield('title', 'Administración')</h1>
            </div>
            <div class="ml-auto flex items-center gap-2">
                @include('partials.restaurant-switch', ['class' => 'field w-auto'])
                <a href="{{ route('pos.index') }}" class="btn-primary"><i class="fa fa-desktop"></i><span class="hidden sm:inline">Ir al POS</span></a>
                <span class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold sm:flex"><i class="fa fa-user text-brand-600"></i>{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary" title="Salir"><i class="fa fa-sign-out"></i></button>
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

            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
