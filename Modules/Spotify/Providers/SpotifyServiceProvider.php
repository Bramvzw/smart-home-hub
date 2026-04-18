<?php

namespace Modules\Spotify\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Spotify\Services\SpotifyService;

class SpotifyServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Spotify';
    protected string $nameLower = 'spotify';

    public function register(): void
    {
        parent::register();
        $this->app->singleton(SpotifyService::class);
    }

    public function boot(): void
    {
        parent::boot();
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
