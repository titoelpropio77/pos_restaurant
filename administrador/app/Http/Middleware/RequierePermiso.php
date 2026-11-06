<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un permiso del rol de la cuenta: Route::middleware('permiso:restaurantes.ver').
 */
class RequierePermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        abort_unless($request->user()?->puede($permiso), 403, 'Su rol no tiene permiso para esta sección.');

        return $next($request);
    }
}
