<?php

namespace Modules\Deals\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PricePointResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'price' => $this->price !== null ? (float) $this->price : null,
            'observed_at' => $this->observed_at?->toIso8601String(),
        ];
    }
}
