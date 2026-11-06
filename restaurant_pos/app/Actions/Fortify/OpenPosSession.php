<?php

namespace App\Actions\Fortify;

use App\Exceptions\PosException;
use App\Http\Controllers\Pos\SessionController;
use App\Models\User;
use App\Models\UserLogin;
use App\Support\PosContext;
use App\Support\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Paso propio del POS en el ingreso de Fortify: valida que el usuario pueda abrir caja,
 * registra el ingreso y prepara la sesión de trabajo (sucursal, almacén, tipo de cambio).
 */
class OpenPosSession
{
    public function __construct(private PosContext $pos) {}

    public function handle(Request $request, Closure $next): mixed
    {
        /** @var User $user */
        $user = Auth::user();

        $restaurants = $user->activeRestaurantIds();

        try {
            if (! $restaurants) {
                throw new PosException('EL RESTAURANTE ESTÁ INACTIVO, COMUNÍQUESE CON EL ADMINISTRADOR');
            }

            // Hasta aquí la petición era de invitado: recién ahora se conoce el restaurante
            Tenant::use($user->preferredRestaurantId($restaurants));
            $this->open($request, $user, (float) $request->input('exchangeRate'));
            $request->session()->put(Tenant::SESSION_KEY, Tenant::id());
        } catch (PosException $e) {
            Auth::logout();

            return back()->with('error_login', $e->getMessage());
        }

        return $next($request);
    }

    /** Abre la caja en el restaurante actual. También se usa al cambiar de restaurante. */
    public function open(Request $request, User $user, float $sentExchangeRate): void
    {
        $pointOfSale = $user->pointOfSale()->with('branch')->first();

        $error = match (true) {
            ! $pointOfSale => 'EL USUARIO NO TIENE UN PUNTO DE VENTA ASIGNADO',
            ! $pointOfSale->can_sell, ! $user->can('pos.access') => 'EL USUARIO NO ESTÁ HABILITADO PARA VENDER',
            (bool) UserLogin::latest('id')->first()?->login_date?->isAfter(today()) => 'LA FECHA NO ES LA ACTUAL, POR FAVOR COLOQUE LA FECHA CORRECTAMENTE',
            default => null,
        };

        if ($error) {
            throw new PosException($error);
        }

        $exchangeRate = $this->exchangeRate($request, $sentExchangeRate);

        DB::transaction(function () use ($user, $exchangeRate, $pointOfSale) {
            UserLogin::create([
                'login_count' => (int) UserLogin::max('login_count') + 1,
                'user_id' => $user->id,
                'username' => $user->username,
                'login_date' => today(),
                'time' => now()->format('G:i'),
                'exchange_rate' => $exchangeRate,
            ]);

            $this->pos->start($pointOfSale, $exchangeRate);
        });
    }

    /**
     * La pantalla de ingreso no sabe de qué restaurante es el usuario, así que no puede mostrar
     * su tipo de cambio: se conserva el último del restaurante salvo que un administrador
     * suyo haya autorizado cambiarlo.
     */
    private function exchangeRate(Request $request, float $sent): float
    {
        if (in_array(Tenant::id(), (array) $request->session()->pull(SessionController::EXCHANGE_RATE_AUTHORIZED), true)) {
            return $sent;
        }

        return (float) UserLogin::latest('id')->value('exchange_rate') ?: $sent;
    }
}
