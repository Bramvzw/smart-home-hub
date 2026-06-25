<?php

namespace Modules\Deals\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListingResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'retailer' => $this->retailer,
            'external_id' => $this->external_id,
            'title' => $this->title,
            'url' => $this->url,
            'current_price' => $this->current_price !== null ? (float) $this->current_price : null,
            'lowest_price' => $this->lowest_price !== null ? (float) $this->lowest_price : null,
            'confirmed' => $this->confirmed,
            'active' => $this->active,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
        ];
    }
}
