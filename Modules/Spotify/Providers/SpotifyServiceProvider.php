<?php

namespace Modules\Spotify\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Modules\Spotify\Services\SpotifyService;
use Modules\Spotify\Services\SpotifyTokenService;

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

    public function health(): ModuleHealth
    {
        $setup = ModuleHealth::require([
            'SPOTIFY_CLIENT_ID' => config('services.spotify.client_id'),
            'SPOTIFY_CLIENT_SECRET' => config('services.spotify.client_secret'),
            'SPOTIFY_REDIRECT_URI' => config('services.spotify.redirect_uri'),
        ]);

        if (! $setup->isOk()) {
            return $setup;
        }

        if (! app(SpotifyTokenService::class)->hasStoredAuthorization()) {
            return ModuleHealth::needsSetup([
                'Spotify-account nog niet gekoppeld — verbind via de knop op de Spotify-pagina',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
