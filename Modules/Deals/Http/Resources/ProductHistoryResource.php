<?php

namespace Modules\Deals\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Stable price-history contract for a watched product. Expects the product to
 * be loaded with `listings.pricePoints` to avoid N+1 queries.
 */
class ProductHistoryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->id,
            'listings' => $this->listings->map(fn ($listing): array => [
                'id' => $listing->id,
                'retailer' => $listing->retailer,
                'title' => $listing->title,
                'price_points' => PricePointResource::collection($listing->pricePoints)->resolve($request),
            ])->values()->all(),
        ];
    }
}
