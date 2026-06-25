<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurrenceResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'board_id' => $this->board_id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'cadence_type' => $this->cadence_type,
            'cadence_config' => $this->cadence_config ?? [],
            'notify' => $this->notify,
            'active' => $this->active,
            'next_due_on' => $this->next_due_on?->toDateString(),
            'last_materialized_on' => $this->last_materialized_on?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
