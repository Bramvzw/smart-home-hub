<?php

namespace Modules\Deals\Services;

use Illuminate\Support\Facades\Log;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Data\ListingCandidate;
use Throwable;

class ProductMatcher
{
    public function __construct(private readonly iterable $adapters) {}

    /**
     * @return list<ListingCandidate>
     */
    public function findCandidates(string $query): array
    {
        $candidates = [];

        foreach ($this->adapters as $adapter) {
            /** @var RetailerAdapter $adapter */
            if (! in_array($adapter->retailer(), (array) config('deals.retailers', []), true)) {
                continue;
            }

            try {
                array_push($candidates, ...$adapter->search($query));
            } catch (Throwable $exception) {
                Log::warning('Deal retailer search failed.', [
                    'retailer' => $adapter->retailer(),
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $candidates;
    }
}
