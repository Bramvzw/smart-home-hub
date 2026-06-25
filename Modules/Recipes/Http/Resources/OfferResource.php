<?php

namespace Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store' => $this->store,
            'external_id' => $this->external_id,
            'product_name' => $this->product_name,
            'category' => $this->category,
            'normal_price' => $this->normal_price !== null ? (float) $this->normal_price : null,
            'offer_price' => $this->offer_price !== null ? (float) $this->offer_price : null,
            'discount_label' => $this->discount_label,
            'unit' => $this->unit,
            'image_url' => $this->image_url,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_to' => $this->valid_to?->toDateString(),
            'week_key' => $this->week_key,
            'fetched_at' => $this->fetched_at?->toIso8601String(),
        ];
    }
}
