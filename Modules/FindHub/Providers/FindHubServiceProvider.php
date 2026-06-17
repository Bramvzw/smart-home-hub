<?php

namespace Modules\FindHub\Providers;

use App\Providers\ModuleServiceProvider;

class FindHubServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'FindHub';
    protected string $nameLower = 'findhub';

    public function getModuleName(): string
    {
        return 'Find Hub';
    }

    public function getModuleSlug(): string
    {
        return 'findhub';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Find Hub', 'route' => 'findhub.index', 'icon' => 'map-pin'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
