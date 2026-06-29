<?php

namespace Modules\Calendar\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Modules\Calendar\Briefing\CalendarBriefingSource;

class CalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Calendar';

    protected string $nameLower = 'calendar';

    public function register(): void
    {
        parent::register();

        $this->app->tag([CalendarBriefingSource::class], 'briefing.source');
    }

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

    public function health(): ModuleHealth
    {
        if (config('calendar.feeds') === []) {
            return ModuleHealth::needsSetup([
                'Geen agenda-feeds ingesteld — vul CALENDAR_ICS_FEEDS (één per regel: "label | kleur | url")',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        return null;
    }
}
