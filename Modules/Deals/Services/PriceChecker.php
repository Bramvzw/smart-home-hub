<?php

namespace Modules\Deals\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Models\ProductListing;
use Throwable;

class PriceChecker
{
    public function __construct(private readonly iterable $adapters) {}

    public function check(ProductListing $listing): ?array
    {
        $adapter = $this->adapterFor($listing->retailer);

        if (! $adapter) {
            return null;
        }

        try {
            $price = $adapter->fetchPrice($listing);
        } catch (Throwable $exception) {
            Log::warning('Deal retailer price check failed.', [
                'retailer' => $listing->retailer,
                'listing' => $listing->id,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if ($price === null) {
            return null;
        }

        $previous = $listing->current_price !== null ? (float) $listing->current_price : null;
        $oldLowest = $listing->lowest_price !== null ? (float) $listing->lowest_price : null;
        $lowest = $oldLowest === null ? $price : min($oldLowest, $price);
        $observedAt = CarbonImmutable::now((string) config('app.timezone', 'UTC'));

        $listing->pricePoints()->create([
            'price' => $price,
            'observed_at' => $observedAt,
        ]);
        $listing->forceFill([
            'current_price' => $price,
            'lowest_price' => $lowest,
            'last_checked_at' => $observedAt,
        ])->save();

        return [
            'dropped' => $previous !== null && $price < $previous,
            'old_price' => $previous,
            'new_price' => $price,
            'lowest_ever' => $oldLowest === null || $price < $oldLowest,
        ];
    }

    private function adapterFor(string $retailer): ?RetailerAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->retailer() === $retailer) {
                return $adapter;
            }
        }

        return null;
    }
}
