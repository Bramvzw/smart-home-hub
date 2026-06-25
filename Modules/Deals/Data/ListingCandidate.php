<?php

namespace Modules\Deals\Data;

final readonly class ListingCandidate
{
    public function __construct(
        public string $retailer,
        public ?string $externalId,
        public string $title,
        public string $url,
        public ?float $price = null,
        public ?string $imageUrl = null,
    ) {
    }
}
