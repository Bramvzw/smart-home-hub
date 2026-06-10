<?php

namespace Modules\Calendar\Providers;

use App\Providers\ModuleServiceProvider;

class CalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Calendar';

    protected string $nameLower = 'calendar';

    public function getModuleName(): string
    {
        return 'Calendar';
    }

    public function getModuleSlug(): string
    {
        return 'calendar';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar'],
        ];
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
