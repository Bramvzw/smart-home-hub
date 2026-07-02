<?php

namespace Modules\Calendar\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Schema;
use Modules\Calendar\Briefing\CalendarBriefingSource;
use Modules\Calendar\Contracts\PlanComposer;
use Modules\Calendar\Models\CalendarPlan;
use Modules\Calendar\Services\DeterministicPlanComposer;
use Modules\Calendar\Services\Google\GoogleCalendarTokenService;

class CalendarServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Calendar';

    protected string $nameLower = 'calendar';

    public function register(): void
    {
        parent::register();

        $this->app->bind(PlanComposer::class, DeterministicPlanComposer::class);
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
        $setup = ModuleHealth::require([
            'GOOGLE_CLIENT_ID' => config('calendar.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('calendar.google.client_secret'),
            'GOOGLE_REDIRECT' => config('calendar.google.redirect'),
            'HUB_AI_ANTHROPIC_API_KEY' => config('ai.anthropic.api_key'),
        ]);

        if (! $setup->isOk()) {
            return $setup;
        }

        $connected = Schema::hasTable('google_calendar_tokens')
            && app(GoogleCalendarTokenService::class)->connected();

        if (! $connected) {
            return ModuleHealth::needsSetup([
                'Google Calendar nog niet gekoppeld — verbind via de knop op de Calendar-pagina',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('planner_plans')) {
            return null;
        }

        $plan = CalendarPlan::latestGenerated()->first();

        return $plan ? "Plan {$plan->week_key}: {$plan->status}" : 'No plan yet';
    }
}
