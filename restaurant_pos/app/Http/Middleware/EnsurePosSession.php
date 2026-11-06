<?php

namespace App\Http\Middleware;

use App\Support\PosContext;
use App\Support\PosResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un cajero autenticado con sucursal de trabajo. Las peticiones AJAX reciben
 * {"error": "Error Session"}, que el frontend del POS interpreta como sesión vencida.
 */
class EnsurePosSession
{
    public function __construct(private PosContext $pos) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $this->pos->active()) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson()) {
            return PosResponse::error('Error Session');
        }

        return redirect()->route('pos.index');
    }
}
