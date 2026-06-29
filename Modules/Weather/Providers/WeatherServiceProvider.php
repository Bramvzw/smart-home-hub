<?php

namespace Modules\Weather\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Modules\Weather\Briefing\WeatherBriefingSource;
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

        $this->app->tag([WeatherBriefingSource::class], 'briefing.source');
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

    public function health(): ModuleHealth
    {
        if ((string) config('weather.ntfy.topic', '') === '') {
            return ModuleHealth::degraded([
                'Weeralerts uit — geen ntfy-topic ingesteld (WEATHER_NTFY_TOPIC of PHONE_PING_NTFY_TOPIC)',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
