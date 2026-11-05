<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel de administración: sólo usuarios activos de nivel administrador.
 */
class SoloAdministrador
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return redirect()->route('pos.index');
        }

        abort_unless($usuario->esAdministrador() && $usuario->estaActivo(), 403, 'Sólo los administradores pueden ingresar al panel.');

        return $next($request);
    }
}
