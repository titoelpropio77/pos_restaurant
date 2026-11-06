<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Support\Restaurante;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el restaurante del usuario autenticado. Debe correr antes de SubstituteBindings para
 * que los parámetros de ruta ({venta}, {usuario}...) también se resuelvan filtrados.
 *
 * Si el usuario o su restaurante fueron dados de baja, la sesión se cierra en el acto.
 */
class EstablecerRestaurante
{
    public function __construct(private Restaurante $restaurante) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = Auth::user();

        if ($usuario && ! ($usuario->estaActivo() && Empresa::whereKey($usuario->empresa_id)->where('estado', 'ACTIVO')->exists())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $usuario = null;
        }

        $this->restaurante->iniciarPeticion($usuario?->empresa_id);

        return $next($request);
    }

    /** Las respuestas en streaming (CSV) consultan después de salir de la pila de middleware. */
    public function terminate(Request $request, Response $response): void
    {
        $this->restaurante->terminarPeticion();
    }
}
