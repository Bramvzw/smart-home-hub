<?php

namespace Modules\Calendar\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Stable JSON contract for a habit card (App\Data\HabitCard DTO).
 *
 * The DTO already owns the canonical shape; this resource keeps model/DTO JSON
 * off the controller boundary while leaving that shape byte-for-byte unchanged.
 *
 * @property-read \App\Data\HabitCard $resource
 */
class HabitResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
