<?php

namespace App\Providers;

use App\Auth\UserProvider;
use App\Support\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Tenant::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('users', fn ($app, array $config) => new UserProvider($app['hash'], $config['model']));
    }
}
