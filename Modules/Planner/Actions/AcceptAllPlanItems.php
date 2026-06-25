<?php

namespace Modules\Planner\Actions;

use Modules\Planner\Models\PlannerPlan;
use Throwable;

class AcceptAllPlanItems
{
    /**
     * Items that could not be inserted into Google Calendar during the last run.
     *
     * @var list<array{item_id: int, message: string}>
     */
    public array $failures = [];

    public function __construct(private readonly AcceptPlanItem $acceptPlanItem) {}

    public function __invoke(PlannerPlan $plan): PlannerPlan
    {
        $this->failures = [];

        // Eager-load the related intention and plan so each AcceptPlanItem insert does not re-query them
        // per item (avoids N+1 across the batch).
        $items = $plan->items()->where('status', 'proposed')->with(['intention', 'plan'])->get();

        foreach ($items as $item) {
            // A mid-batch Google insert failure must not abort the whole batch or leave already-accepted
            // items rolled back; collect the failure and keep going so successful inserts persist.
            try {
                ($this->acceptPlanItem)($item);
            } catch (Throwable $e) {
                $this->failures[] = ['item_id' => $item->id, 'message' => $e->getMessage()];
            }
        }

        return $plan->fresh('items');
    }
}
