<?php

namespace Modules\Planner\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Schema;
use Modules\Planner\Contracts\PlanComposer;
use Modules\Planner\Models\PlannerPlan;
use Modules\Planner\Services\PrismPlanComposer;

class PlannerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Planner';
    protected string $nameLower = 'planner';

    public function register(): void
    {
        parent::register();

        $this->app->bind(PlanComposer::class, PrismPlanComposer::class);
    }

    public function getModuleName(): string
    {
        return 'Planner';
    }

    public function getModuleSlug(): string
    {
        return 'planner';
    }

    public function getNavigation(): array
    {
        return [['label' => 'Agenda-planner', 'route' => 'planner.index', 'icon' => 'planner']];
    }

    public function health(): ModuleHealth
    {
        $setup = ModuleHealth::require([
            'GOOGLE_CLIENT_ID' => config('planner.google.client_id'),
            'GOOGLE_CLIENT_SECRET' => config('planner.google.client_secret'),
            'GOOGLE_REDIRECT' => config('planner.google.redirect'),
            'HUB_AI_ANTHROPIC_API_KEY' => config('ai.anthropic.api_key'),
        ]);

        if (! $setup->isOk()) {
            return $setup;
        }

        $connected = Schema::hasTable('google_calendar_tokens')
            && app(\Modules\Planner\Services\Google\GoogleCalendarTokenService::class)->connected();

        if (! $connected) {
            return ModuleHealth::needsSetup([
                'Google Calendar nog niet gekoppeld — verbind via de knop op de Planner-pagina',
            ]);
        }

        return ModuleHealth::ok();
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('planner_plans')) {
            return null;
        }

        $plan = PlannerPlan::query()->latest('generated_at')->first();

        return $plan ? "Plan {$plan->week_key}: {$plan->status}" : 'No plan yet';
    }
}
