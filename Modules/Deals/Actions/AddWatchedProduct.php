<?php

namespace Modules\Deals\Actions;

use Modules\Deals\Models\WatchedProduct;
use Modules\Deals\Services\ProductMatcher;

class AddWatchedProduct
{
    public function __construct(private readonly ProductMatcher $matcher) {}

    public function __invoke(string $name): WatchedProduct
    {
        $query = mb_strtolower(trim($name));
        $product = WatchedProduct::query()->create([
            'name' => trim($name),
            'query' => $query,
        ]);

        foreach ($this->matcher->findCandidates($query) as $candidate) {
            $product->listings()->updateOrCreate(
                [
                    'retailer' => $candidate->retailer,
                    'external_id' => $candidate->externalId ?: sha1($candidate->retailer.$candidate->url),
                ],
                [
                    'title' => $candidate->title,
                    'url' => $candidate->url,
                    'current_price' => $candidate->price,
                    'lowest_price' => $candidate->price,
                    'confirmed' => false,
                    'active' => true,
                ]
            );
        }

        return $product->fresh('listings');
    }
}
