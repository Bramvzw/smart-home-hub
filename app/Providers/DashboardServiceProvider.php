<?php

namespace App\Providers;

use App\Services\Ntfy\HubNotifier;
use App\Services\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('ntfy.php'), 'ntfy');

        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(HubNotifier::class, fn () => new HubNotifier(
            url: rtrim((string) config('ntfy.url', 'https://ntfy.sh'), '/'),
            topic: (string) config('ntfy.topic', ''),
            token: (string) config('ntfy.token', ''),
            timeout: (int) config('ntfy.timeout', 10),
        ));
    }

    public function boot(): void
    {
        //
    }
}
