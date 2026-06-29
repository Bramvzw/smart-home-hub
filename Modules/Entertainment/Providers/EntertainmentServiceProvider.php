<?php

namespace Modules\Entertainment\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Schema;
use Modules\Entertainment\Contracts\EntertainmentCurator;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\MusicRelease;
use Modules\Entertainment\Services\Concerts\BandsintownProvider;
use Modules\Entertainment\Services\Concerts\HedonProvider;
use Modules\Entertainment\Services\Concerts\TicketmasterProvider;
use Modules\Entertainment\Services\PrismEntertainmentCurator;

class EntertainmentServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Entertainment';
    protected string $nameLower = 'entertainment';

    public function register(): void
    {
        parent::register();

        $this->app->bind(EntertainmentCurator::class, PrismEntertainmentCurator::class);
        $this->app->tag([TicketmasterProvider::class, BandsintownProvider::class, HedonProvider::class], 'entertainment.concert_provider');
        $this->app->when(\Modules\Entertainment\Actions\RefreshConcerts::class)
            ->needs('$providers')
            ->giveTagged('entertainment.concert_provider');
    }

    public function getModuleName(): string
    {
        return 'Entertainment';
    }

    public function getModuleSlug(): string
    {
        return 'entertainment';
    }

    public function getNavigation(): array
    {
        return [['label' => 'Entertainment', 'route' => 'entertainment.index', 'icon' => 'entertainment']];
    }

    public function health(): ModuleHealth
    {
        $setup = ModuleHealth::require([
            'TMDB_API_KEY' => config('entertainment.tmdb.api_key'),
            'HUB_AI_ANTHROPIC_API_KEY' => config('ai.anthropic.api_key'),
        ]);

        if (! $setup->isOk()) {
            return $setup;
        }

        if (config('entertainment.concerts.ticketmaster_key') === '' && config('entertainment.concerts.bandsintown_key') === '') {
            return ModuleHealth::degraded([
                'Concertaanbevelingen uit — TICKETMASTER_KEY of BANDSINTOWN_KEY ontbreekt',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('concerts') || ! Schema::hasTable('music_releases')) {
            return null;
        }

        return Concert::query()->whereIn('relevance', ['followed', 'hedon', 'might_like'])->count().' relevant concerts, '.MusicRelease::query()->count().' releases';
    }
}
