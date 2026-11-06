<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213D">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32 48x48">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/brand/icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>@yield('title', 'Administración') · Mesatek Admin</title>
</head>
<body class="bg-base text-ink antialiased" x-data="{ menu: false }">
@php
    $account = auth()->user();
    $menu = [
        'General' => [
            ['dashboard', 'fa-tachometer', 'Inicio', 'dashboard', 'dashboard.view'],
        ],
        'Restaurantes' => [
            ['restaurants.index', 'fa-cutlery', 'Restaurantes', 'restaurants.*', 'restaurants.view'],
            ['branches.index', 'fa-building-o', 'Sucursales', 'branches.*', 'branches.view'],
            ['users.index', 'fa-users', 'Usuarios', 'users.*', 'users.view'],
        ],
        'Seguridad' => [
            ['admins.index', 'fa-shield', 'Cuentas del sistema', 'admins.*', 'admins.view'],
            ['roles.index', 'fa-id-card-o', 'Roles', 'roles.*', 'roles.view'],
            ['permissions.index', 'fa-key', 'Permisos', 'permissions.*', 'permissions.view'],
            ['audit.index', 'fa-history', 'Auditoría', 'audit.*', 'audit.view'],
        ],
        'Catálogos' => collect(\App\Admin\Catalogs::all())
            ->map(fn ($c, $slug) => [['catalogs.index', $slug], $c['icon'], $c['title'], $slug, "catalogs.{$slug}.view"])
            ->values()->all(),
    ];
    $active = fn ($pattern) => str_contains($pattern, '.') || $pattern === 'dashboard'
        ? request()->routeIs($pattern)
        : request()->route('catalog') === $pattern;
@endphp

<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-panel text-slate-300 transition lg:translate-x-0" :class="menu && 'translate-x-0'">
        <a href="{{ route('dashboard') }}" class="block px-5 pt-4 pb-1">
            <img src="{{ asset('assets/brand/logo-dark.png') }}" class="h-10 w-auto" alt="Mesatek">
        </a>
        <p class="px-5 text-[11px] font-semibold tracking-wider text-brand-400 uppercase">Administración del sistema</p>
        <nav class="px-3 pb-6">
            @foreach ($menu as $category_group => $items)
                @php $items = array_filter($items, fn ($item) => $item[4] === null || $account->can($item[4])); @endphp
                @continue(! $items)
                <p class="mt-4 mb-1 px-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $category_group }}</p>
                @foreach ($items as [$route, $icon, $text, $pattern])
                    <a href="{{ is_array($route) ? route($route[0], $route[1]) : route($route) }}" @class([
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
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="btn-secondary btn-small lg:hidden" @click="menu = true"><i class="fa fa-bars"></i></button>
            <h1 class="truncate text-lg font-bold">@yield('title', 'Administración')</h1>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('profile.edit') }}" class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold sm:flex" title="Mi cuenta">
                    <i class="fa fa-user text-brand-600"></i>{{ $account->name }}
                    <span class="text-xs font-normal text-muted">{{ $account->roles->first()?->label }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secondary" title="Salir"><i class="fa fa-sign-out"></i></button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-4 sm:p-6">
            @if (session('ok'))
                <div class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                    <i class="fa fa-check-circle"></i>{{ session('ok') }}
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
</body>
</html>
