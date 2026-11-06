<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::loginView(fn () => view('session.login'));

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by(
            mb_strtolower((string) $request->input('email')).'|'.$request->ip(),
        ));

        Fortify::authenticateUsing(function (Request $request) {
            $admin = Admin::where('email', mb_strtolower((string) $request->input('email')))
                ->where('status', 'ACTIVE')
                ->first();

            return $admin && Hash::check((string) $request->input('password'), $admin->password) ? $admin : null;
        });
    }
}
