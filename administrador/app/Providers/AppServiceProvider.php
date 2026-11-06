<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // restaurant_pos es el dueño del esquema; el contenedor monta sus migraciones sólo para las pruebas
        if (is_dir('/var/www/migraciones')) {
            $this->loadMigrationsFrom('/var/www/migraciones');
        }

        Paginator::defaultView('pagination::tailwind');
    }
}
