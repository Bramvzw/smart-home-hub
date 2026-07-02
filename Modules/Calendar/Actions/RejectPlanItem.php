<?php

namespace Modules\Calendar\Actions;

use Modules\Calendar\Models\CalendarPlanItem;

class RejectPlanItem
{
    public function __invoke(CalendarPlanItem $item): CalendarPlanItem
    {
        $item->update(['status' => 'rejected']);

        return $item->fresh();
    }
}
