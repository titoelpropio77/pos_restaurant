<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el restaurante con el que trabaja el usuario autenticado. Debe correr antes de SubstituteBindings para
 * que los parámetros de ruta ({venta}, {usuario}...) también se resuelvan filtrados.
 *
 * Si el usuario o su restaurante fueron dados de baja, la sesión se cierra en el acto.
 */
class SetTenant
{
    public function __construct(private Tenant $restaurant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $restaurantId = $user ? $this->resolve($request, $user) : null;

        if ($user && ! $restaurantId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->restaurant->startRequest($restaurantId);

        return $next($request);
    }

    /**
     * El restaurante de la sesión se valida en cada petición contra las membresías del usuario.
     * Si ya no le corresponde se cierra la sesión: la caja abierta era de ese restaurante.
     */
    private function resolve(Request $request, User $user): ?int
    {
        $available = $user->isActive() ? $user->activeRestaurantIds() : [];

        if ($request->session()->has(Tenant::SESSION_KEY)) {
            $restaurantId = (int) $request->session()->get(Tenant::SESSION_KEY);

            return in_array($restaurantId, $available, true) ? $restaurantId : null;
        }

        $restaurantId = $user->preferredRestaurantId($available);

        if ($restaurantId) {
            $request->session()->put(Tenant::SESSION_KEY, $restaurantId);
        }

        return $restaurantId;
    }

    /** Las respuestas en streaming (CSV) consultan después de salir de la pila de middleware. */
    public function terminate(Request $request, Response $response): void
    {
        $this->restaurant->finishRequest();
    }
}
