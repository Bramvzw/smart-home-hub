<?php

namespace Modules\Weather\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Weather\Services\NtfyWeatherNotifier;

class WeatherServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Weather';

    protected string $nameLower = 'weather';

    public function register(): void
    {
        parent::register();

        $this->app->singleton(NtfyWeatherNotifier::class, fn () => new NtfyWeatherNotifier(
            url: rtrim((string) config('weather.ntfy.url', 'https://ntfy.sh'), '/'),
            topic: (string) config('weather.ntfy.topic', ''),
            token: (string) config('weather.ntfy.token', ''),
        ));
    }

    public function getModuleName(): string
    {
        return 'Weather';
    }

    public function getModuleSlug(): string
    {
        return 'weather';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Weather', 'route' => 'weather.index', 'icon' => 'weather'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
