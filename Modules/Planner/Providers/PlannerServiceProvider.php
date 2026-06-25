<?php

namespace Modules\Planner\Providers;

use App\Providers\ModuleServiceProvider;
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

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('planner_plans')) {
            return null;
        }

        $plan = PlannerPlan::query()->latest('generated_at')->first();

        return $plan ? "Plan {$plan->week_key}: {$plan->status}" : 'No plan yet';
    }
}
