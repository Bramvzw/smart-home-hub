<?php

namespace Modules\Planner\Contracts;

use Modules\Planner\Data\ComposedPlan;

interface PlanComposer
{
    /**
     * @param  list<\Modules\Planner\Data\PlanItemData>  $items
     * @param  list<\Modules\Planner\Data\BusyTime>  $busy
     */
    public function compose(array $items, array $busy): ComposedPlan;
}
