<?php

namespace Modules\Planner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannerIntentionResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'frequency_type' => $this->frequency_type,
            'target_min' => $this->target_min,
            'target_max' => $this->target_max,
            'preferred_windows' => $this->preferred_windows ?? [],
            'duration_minutes' => $this->duration_minutes,
            'location' => $this->location,
            'active' => $this->active,
        ];
    }
}
