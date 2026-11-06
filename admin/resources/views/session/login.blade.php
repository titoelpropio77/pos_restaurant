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
    <title>Ingreso · Mesatek Admin</title>
</head>
<body class="flex min-h-screen items-center justify-center bg-linear-to-br from-panel to-panel-2 p-4 antialiased">
    <main class="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-2xl">
        {{-- El fondo debe ser el mismo crema del logo --}}
        <div class="border-b border-line bg-base px-7 pt-7 pb-5 text-center">
            <h1 class="leading-none"><img src="{{ asset('assets/brand/logo-light.png') }}" class="mx-auto w-60" alt="Mesatek"></h1>
            <p class="mt-3 text-xs font-semibold tracking-widest text-brand-600 uppercase">Administración del sistema</p>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="space-y-4 p-7">
            @csrf
            @if (session('error') || $errors->any())
                <p class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                    <i class="fa fa-exclamation-circle"></i> {{ session('error') ?? $errors->first() }}
                </p>
            @endif

            <x-field name="email" label="Correo" type="email" />
            <x-field name="password" label="Contraseña" type="password" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-brand-500"> Mantener la sesión</label>

            <button class="btn-primary w-full"><i class="fa fa-sign-in"></i> Ingresar</button>
        </form>
    </main>
</body>
</html>
