<?php

namespace Modules\Calendar\Actions;

use Modules\Calendar\Models\CalendarPlanItem;
use Modules\Calendar\Services\Google\GoogleCalendarClient;

class AcceptPlanItem
{
    public function __construct(private readonly GoogleCalendarClient $calendar) {}

    public function __invoke(CalendarPlanItem $item): CalendarPlanItem
    {
        if ($item->status === 'accepted' && $item->google_event_id) {
            return $item;
        }

        $eventId = $this->calendar->insertEvent($item);
        $item->update(['status' => 'accepted', 'google_event_id' => $eventId]);
        $this->syncPlanStatus($item->plan);

        return $item->fresh();
    }

    private function syncPlanStatus($plan): void
    {
        $proposed = $plan->items()->where('status', 'proposed')->count();
        $accepted = $plan->items()->where('status', 'accepted')->count();
        $plan->update(['status' => $proposed === 0 && $accepted > 0 ? 'accepted' : 'partly_accepted']);
    }
}
