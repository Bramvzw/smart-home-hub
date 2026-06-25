<?php

namespace Modules\Deals\Contracts;

use Modules\Deals\Data\ListingCandidate;
use Modules\Deals\Models\ProductListing;

interface RetailerAdapter
{
    public function retailer(): string;

    /**
     * @return list<ListingCandidate>
     */
    public function search(string $query): array;

    public function fetchPrice(ProductListing $listing): ?float;
}
