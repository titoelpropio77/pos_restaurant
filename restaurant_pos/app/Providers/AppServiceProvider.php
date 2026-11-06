<?php

namespace App\Providers;

use App\Auth\UsuarioProvider;
use App\Support\Restaurante;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Restaurante::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('usuarios', fn ($app, array $config) => new UsuarioProvider($app['hash'], $config['model']));
    }
}
