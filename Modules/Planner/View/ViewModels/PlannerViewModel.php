<?php

namespace Modules\Planner\View\ViewModels;

use Modules\Planner\Http\Resources\PlannerIntentionResource;
use Modules\Planner\Http\Resources\PlannerPlanResource;
use Modules\Planner\Models\PlannerIntention;
use Modules\Planner\Models\PlannerPlan;
use Modules\Planner\Services\Google\GoogleCalendarTokenService;

class PlannerViewModel
{
    public function __construct(private readonly GoogleCalendarTokenService $tokens) {}

    public function state(): array
    {
        $plan = PlannerPlan::query()->with('items.intention')->latest('generated_at')->first();

        return [
            'connected' => $this->tokens->connected(),
            'plan' => $plan ? PlannerPlanResource::make($plan)->resolve() : null,
            'intentions' => PlannerIntentionResource::collection(PlannerIntention::query()->orderBy('category')->orderBy('title')->get())->resolve(),
        ];
    }
}
