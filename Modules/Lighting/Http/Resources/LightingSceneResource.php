<?php

namespace Modules\Lighting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Models\LightingScene;

/**
 * @property-read LightingScene $resource
 */
class LightingSceneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'accent' => $this->resource->accent,
            'sort_order' => $this->resource->sort_order,
            'lights' => array_map(static fn (SceneLightState $state): array => $state->toArray(), $this->resource->states()),
        ];
    }
}
