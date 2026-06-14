<?php

namespace Modules\Lighting\Providers;

use App\Providers\ModuleServiceProvider;
use Modules\Lighting\Services\Providers\GoveeApiClient;

class LightingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Lighting';

    protected string $nameLower = 'lighting';

    public function register(): void
    {
        parent::register();

        // One instance per request so the inter-command rate-limit window is
        // shared across every control call made while rendering or acting.
        $this->app->singleton(GoveeApiClient::class);
    }

    public function getModuleName(): string
    {
        return 'Lighting';
    }

    public function getModuleSlug(): string
    {
        return 'lighting';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Lighting', 'route' => 'lighting.index', 'icon' => 'lighting'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
