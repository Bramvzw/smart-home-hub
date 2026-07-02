<?php

namespace Modules\Calendar\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalendarPlanItemResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recurrence_id' => $this->recurrence_id,
            'title' => $this->title,
            'category' => $this->category,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'status' => $this->status,
            'unplaceable_reason' => $this->unplaceable_reason,
            'google_event_id' => $this->google_event_id,
        ];
    }
}
