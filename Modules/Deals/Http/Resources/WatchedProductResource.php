<?php

namespace Modules\Deals\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Deals\Support\SafeUrl;

/** @mixin \Modules\Deals\Models\WatchedProduct */
class WatchedProductResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'query' => $this->query,
            'category' => $this->category,
            'image_url' => SafeUrl::http($this->image_url),
            'notes' => $this->notes,
            'listings' => ProductListingResource::collection($this->whenLoaded('listings'))->resolve($request),
        ];
    }
}
