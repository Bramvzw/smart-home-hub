<?php

namespace Modules\Lighting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Lighting\Data\Light;

/**
 * @property-read Light $resource
 */
class LightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
