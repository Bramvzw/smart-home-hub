<?php

namespace Modules\Planner\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlannerPlanResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'week_key' => $this->week_key,
            'status' => $this->status,
            'summary' => $this->summary,
            'is_fallback' => $this->is_fallback,
            'generated_at' => $this->generated_at?->toIso8601String(),
            'items' => PlannerPlanItemResource::collection($this->whenLoaded('items'))->resolve($request),
        ];
    }
}
