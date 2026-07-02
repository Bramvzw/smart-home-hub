<?php

namespace Modules\Calendar\Contracts;

use Modules\Calendar\Data\ComposedPlan;

interface PlanComposer
{
    /**
     * @param  list<\Modules\Calendar\Data\PlanItemData>  $items
     * @param  list<\Modules\Calendar\Data\BusyTime>  $busy
     */
    public function compose(array $items, array $busy): ComposedPlan;
}
