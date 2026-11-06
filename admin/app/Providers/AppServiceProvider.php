<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        if (is_dir('/var/www/migrations')) {
            $this->loadMigrationsFrom('/var/www/migrations');
        }

        Paginator::defaultView('pagination::tailwind');

        // Superadmin es el dueño del sistema: pasa toda comprobación, incluidos los permisos futuros
        Gate::before(fn ($account) => $account instanceof Admin && $account->isSuperAdmin() ? true : null);

        Event::listen(function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->save();
            AuditLog::register('LOGIN', 'session', $event->user->id, "{$event->user->email} ingresó al sistema");
        });
    }
}
