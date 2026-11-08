<?php

use App\Exceptions\PosException;
use App\Http\Middleware\EnsurePosSession;
use App\Http\Middleware\RequirePanelAccess;
use App\Http\Middleware\SetTenant;
use App\Support\PosResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'admin', 'permission:panel.access'])->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
            // Menú digital público (destino del QR): sin el grupo web, para no abrir una sesión por cada escaneo
            Route::middleware('throttle:120,1')->group(base_path('routes/menu.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['pos' => EnsurePosSession::class, 'admin' => RequirePanelAccess::class, 'permission' => PermissionMiddleware::class]);
        $middleware->redirectGuestsTo('/');
        $middleware->redirectUsersTo('/');
        $middleware->web(append: [SetTenant::class]);
        // Los parámetros de ruta deben resolverse ya filtrados por restaurante
        $middleware->prependToPriorityList(SubstituteBindings::class, SetTenant::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El frontend del POS espera siempre HTTP 200 con {"error": "...", "result": ...}
        $exceptions->render(function (PosException $e, Request $request) {
            // En el panel de administración el error vuelve al formulario
            if ($request->is('admin', 'admin/*')) {
                return back()->withInput()->with('error', $e->getMessage());
            }

            return PosResponse::error($e->getMessage());
        });
        // El JS del POS no maneja un 403: el permiso denegado también sigue el contrato JSON
        $exceptions->render(function (UnauthorizedException $e, Request $request) {
            if ($request->is('pos/*')) {
                return PosResponse::error('NO TIENE PERMISO PARA ESTA ACCIÓN');
            }
        });
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('pos/*') && $request->ajax()) {
                return PosResponse::error(implode('<br>', $e->validator->errors()->all()));
            }
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
