<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel de administración: sólo usuarios activos de nivel administrador.
 */
class RequirePanelAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('pos.index');
        }

        abort_unless($user->canAccessPanel() && $user->isActive(), 403, 'Sólo los administradores pueden ingresar al panel.');

        return $next($request);
    }
}
