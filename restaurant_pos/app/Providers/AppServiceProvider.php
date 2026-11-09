<?php

namespace App\Providers;

use App\Auth\UserProvider;
use App\Support\Tenant;
use App\Support\VersionedAsset;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
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

        // @versioned('js/pos/pos.js') → URL del archivo con ?v=<fecha de modificación>
        Blade::directive('versioned', fn (string $path) => '<?php echo e('.VersionedAsset::class.'::url('.$path.')); ?>');
    }
}
