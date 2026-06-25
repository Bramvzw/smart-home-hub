<?php

namespace Modules\Recipes\Actions;

use Modules\Recipes\Data\OfferFetchResult;
use Modules\Recipes\Services\OfferAggregator;

class FetchOffers
{
    public function __construct(
        private readonly OfferAggregator $aggregator,
    ) {
    }

    public function __invoke(): OfferFetchResult
    {
        return $this->aggregator->fetch();
    }
}
