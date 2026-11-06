<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PosResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autorización previa al ingreso. El ingreso y la salida los resuelve Fortify (FortifyServiceProvider).
 */
class SessionController extends Controller
{
    public const EXCHANGE_RATE_AUTHORIZED = 'exchange_rate_authorized';

    /**
     * Un partner autoriza modificar el tipo de cambio en la pantalla de ingreso.
     * La autorización vale sólo para sus restaurantes y sólo para el siguiente ingreso.
     */
    public function authorizeExchangeRate(Request $request): JsonResponse
    {
        $user = User::withoutGlobalScopes()
            ->where('email', User::normalize((string) $request->input('email')))
            ->first();

        $authorized = $user?->can('pos.exchange_rate.change') && Hash::check((string) $request->input('password'), $user->password);

        if ($authorized) {
            $request->session()->put(self::EXCHANGE_RATE_AUTHORIZED, $user->activeRestaurantIds());
        }

        return PosResponse::ok($authorized ? 'enabled' : 'disabled');
    }
}
