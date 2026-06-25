<?php

namespace Modules\Planner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannerPlanItemResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'intention_id' => $this->intention_id,
            'title' => $this->title,
            'category' => $this->intention?->category,
            'start_at' => $this->start_at?->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'status' => $this->status,
            'unplaceable_reason' => $this->unplaceable_reason,
            'google_event_id' => $this->google_event_id,
        ];
    }
}
