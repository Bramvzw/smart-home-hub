<?php

namespace Modules\Lighting\Providers;

use App\Providers\ModuleServiceProvider;

class LightingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Lighting';

    protected string $nameLower = 'lighting';

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
