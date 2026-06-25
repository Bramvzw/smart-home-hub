<?php

namespace Modules\Planner\Actions;

use Modules\Planner\Models\PlannerPlanItem;

class RejectPlanItem
{
    public function __invoke(PlannerPlanItem $item): PlannerPlanItem
    {
        $item->update(['status' => 'rejected']);

        return $item->fresh();
    }
}
