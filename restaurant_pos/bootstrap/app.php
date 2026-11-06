<?php

use App\Exceptions\ErrorPos;
use App\Http\Middleware\AsegurarSesionPos;
use App\Http\Middleware\EstablecerRestaurante;
use App\Http\Middleware\RequierePermiso;
use App\Http\Middleware\SoloAdministrador;
use App\Support\RespuestaPos;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'admin'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['pos' => AsegurarSesionPos::class, 'admin' => SoloAdministrador::class, 'permiso' => RequierePermiso::class]);
        $middleware->web(append: [EstablecerRestaurante::class]);
        // Los parámetros de ruta deben resolverse ya filtrados por restaurante
        $middleware->prependToPriorityList(SubstituteBindings::class, EstablecerRestaurante::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El frontend del POS espera siempre HTTP 200 con {"error": "...", "result": ...}
        $exceptions->render(function (ErrorPos $e, Request $request) {
            // En el panel de administración el error vuelve al formulario
            if ($request->is('admin', 'admin/*')) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return RespuestaPos::error($e->getMessage());
        });
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('pos/*') && $request->ajax()) {
                return RespuestaPos::error(implode('<br>', $e->validator->errors()->all()));
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
