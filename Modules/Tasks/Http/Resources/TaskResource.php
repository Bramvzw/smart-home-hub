<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lane_id' => $this->lane_id,
            'title' => $this->title,
            'description' => $this->description,
            'label' => $this->label,
            'priority' => $this->priority,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'notify_before_expiry' => $this->notify_before_expiry,
            'order' => $this->order,
            'is_overdue' => $this->isOverdue(),
            'is_about_to_expire' => $this->isAboutToExpire(),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
