<?php

namespace Modules\Briefing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BriefingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $timezone = (string) config('briefing.timezone', 'Europe/Amsterdam');

        return [
            'date' => $this->date->toDateString(),
            'body' => $this->body,
            'sections' => collect($this->sections ?? [])->map(fn (array $section): array => [
                'key' => (string) ($section['key'] ?? ''),
                'label' => (string) ($section['label'] ?? ''),
                'summary' => (string) ($section['summary'] ?? ''),
            ])->values()->all(),
            'generated_at' => $this->generated_at->setTimezone($timezone)->toIso8601String(),
            'is_fallback' => $this->is_fallback,
            'model' => $this->model,
        ];
    }
}
