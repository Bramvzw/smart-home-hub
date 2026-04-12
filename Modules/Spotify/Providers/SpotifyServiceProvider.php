<?php

namespace Modules\Spotify\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Spotify\Services\SpotifyService;

class SpotifyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Spotify';
    protected string $nameLower = 'spotify';

    public function register(): void
    {
        parent::register();
        $this->app->bind(SpotifyService::class);
    }

    public function boot(): void
    {
        parent::boot();

        RateLimiter::for('spotify-read', function () {
            return Limit::perMinute(60);
        });
        RateLimiter::for('spotify-write', function () {
            return Limit::perMinute(30);
        });
        RateLimiter::for('spotify-search', function () {
            return Limit::perMinute(20);
        });
    }

    public function getModuleName(): string
    {
        return 'Spotify';
    }

    public function getModuleSlug(): string
    {
        return 'spotify';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Spotify', 'route' => 'spotify.index', 'icon' => 'music-note'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
