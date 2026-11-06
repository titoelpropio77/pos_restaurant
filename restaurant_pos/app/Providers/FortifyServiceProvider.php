<?php

namespace App\Providers;

use App\Actions\Fortify\OpenPosSession;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by(
            User::normalize((string) $request->input('email')).'|'.$request->ip(),
        ));

        // El usuario se busca por correo sin el scope de restaurante: autenticarse es lo que lo determina
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::withoutGlobalScopes()
                ->where('email', User::normalize((string) $request->input('email')))
                ->where('status', 'ACTIVE')
                ->first();

            return $user && Hash::check((string) $request->input('password'), $user->password) ? $user : null;
        });

        Fortify::authenticateThrough(fn () => [
            function (Request $request, $next) {
                $request->validate(['exchangeRate' => ['required', 'numeric', 'gt:0']]);

                return $next($request);
            },
            EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
            OpenPosSession::class,
            PrepareAuthenticatedSession::class,
        ]);
    }
}
