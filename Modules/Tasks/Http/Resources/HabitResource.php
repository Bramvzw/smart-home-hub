<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabitResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $recurrence = $this->resource['recurrence'];
        $progress = $this->resource['progress'];

        return array_merge(
            RecurrenceResource::make($recurrence)->toArray($request),
            [
                'progress' => $progress->toArray(),
                'current_streak' => $this->resource['current_streak'],
                'best_streak' => $this->resource['best_streak'],
                'completed_today' => $this->resource['completed_today'],
            ]
        );
    }
}
