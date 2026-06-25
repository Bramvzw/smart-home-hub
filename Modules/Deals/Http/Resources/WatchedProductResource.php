<?php

namespace Modules\Deals\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'image_url' => $this->image_url,
            'notes' => $this->notes,
            'listings' => ProductListingResource::collection($this->whenLoaded('listings'))->resolve($request),
        ];
    }
}
