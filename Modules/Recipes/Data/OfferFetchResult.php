<?php

namespace Modules\Recipes\Data;

final readonly class OfferFetchResult
{
    /**
     * @param  list<string>  $storesFetched
     * @param  list<string>  $storesFailed
     */
    public function __construct(
        public string $weekKey,
        public array $storesFetched,
        public array $storesFailed,
        public int $offersStored,
    ) {
    }

    public function toArray(): array
    {
        return [
            'week_key' => $this->weekKey,
            'stores_fetched' => $this->storesFetched,
            'stores_failed' => $this->storesFailed,
            'offers_stored' => $this->offersStored,
        ];
    }
}
