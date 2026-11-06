<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14213D">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32 48x48">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/marca/icono-192.png') }}">
    <link rel="stylesheet" href="{{ asset('css/fuentes.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/font-awesome.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <title>@yield('titulo', 'Administración') · Mesatek Admin</title>
</head>
<body class="bg-fondo text-tinta antialiased" x-data="{ menu: false }">
@php
    $cuenta = auth()->user();
    $menu = [
        'General' => [
            ['dashboard', 'fa-tachometer', 'Inicio', 'dashboard', null],
        ],
        'Restaurantes' => [
            ['restaurantes.index', 'fa-cutlery', 'Restaurantes', 'restaurantes.*', 'restaurantes.ver'],
            ['sucursales.index', 'fa-building-o', 'Sucursales', 'sucursales.*', 'restaurantes.ver'],
            ['usuarios.index', 'fa-users', 'Usuarios', 'usuarios.*', 'usuarios.ver'],
        ],
        'Seguridad' => [
            ['administradores.index', 'fa-shield', 'Cuentas del sistema', 'administradores.*', 'administradores.gestionar'],
            ['roles.index', 'fa-id-card-o', 'Roles', 'roles.*', 'roles.gestionar'],
            ['permisos.index', 'fa-key', 'Permisos', 'permisos.*', 'roles.gestionar'],
            ['auditoria.index', 'fa-history', 'Auditoría', 'auditoria.*', 'auditoria.ver'],
        ],
        'Catálogos' => collect(\App\Admin\Catalogos::todos())
            ->map(fn ($c, $slug) => [['catalogos.index', $slug], $c['icono'], $c['titulo'], $slug, 'catalogos.gestionar'])
            ->values()->all(),
    ];
    $activo = fn ($patron) => str_contains($patron, '.') || $patron === 'dashboard'
        ? request()->routeIs($patron)
        : request()->route('catalogo') === $patron;
@endphp

<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full overflow-y-auto bg-panel text-slate-300 transition lg:translate-x-0" :class="menu && 'translate-x-0'">
        <a href="{{ route('dashboard') }}" class="block px-5 pt-4 pb-1">
            <img src="{{ asset('assets/marca/logo-oscuro.png') }}" class="h-10 w-auto" alt="Mesatek">
        </a>
        <p class="px-5 text-[11px] font-semibold tracking-wider text-marca-400 uppercase">Administración del sistema</p>
        <nav class="px-3 pb-6">
            @foreach ($menu as $grupo => $items)
                @php $items = array_filter($items, fn ($item) => $item[4] === null || $cuenta->puede($item[4])); @endphp
                @continue(! $items)
                <p class="mt-4 mb-1 px-3 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">{{ $grupo }}</p>
                @foreach ($items as [$ruta, $icono, $texto, $patron])
                    <a href="{{ is_array($ruta) ? route($ruta[0], $ruta[1]) : route($ruta) }}" @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-marca-500 text-panel' => $activo($patron),
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
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-borde bg-white/90 px-4 backdrop-blur sm:px-6">
            <button class="btn-secundario btn-chico lg:hidden" @click="menu = true"><i class="fa fa-bars"></i></button>
            <h1 class="truncate text-lg font-bold">@yield('titulo', 'Administración')</h1>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('perfil.edit') }}" class="hidden items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold sm:flex" title="Mi cuenta">
                    <i class="fa fa-user text-marca-600"></i>{{ $cuenta->nombre }}
                    <span class="text-xs font-normal text-tenue">{{ $cuenta->rol->nombre }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn-secundario" title="Salir"><i class="fa fa-sign-out"></i></button>
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

            @yield('contenido')
        </main>
    </div>
</div>
</body>
</html>
