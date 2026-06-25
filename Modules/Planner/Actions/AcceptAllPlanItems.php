<?php

namespace Modules\Planner\Actions;

use Modules\Planner\Models\PlannerPlan;

class AcceptAllPlanItems
{
    public function __construct(private readonly AcceptPlanItem $acceptPlanItem) {}

    public function __invoke(PlannerPlan $plan): PlannerPlan
    {
        $plan->items()->where('status', 'proposed')->get()->each(fn ($item) => ($this->acceptPlanItem)($item));

        return $plan->fresh('items');
    }
}
