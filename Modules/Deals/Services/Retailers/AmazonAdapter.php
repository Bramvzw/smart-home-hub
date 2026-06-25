<?php

namespace Modules\Deals\Services\Retailers;

use Illuminate\Support\Facades\Http;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Exceptions\RetailerUnavailable;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Services\Retailers\Concerns\ParsesRetailerPayloads;

class AmazonAdapter implements RetailerAdapter
{
    use ParsesRetailerPayloads;

    public function retailer(): string
    {
        return 'amazon';
    }

    public function search(string $query): array
    {
        if (! (bool) config('deals.amazon.enabled', false) || (string) config('deals.amazon.search_url', '') === '') {
            return [];
        }

        $response = Http::timeout((int) config('deals.request_timeout', 15))
            ->acceptJson()
            ->get((string) config('deals.amazon.search_url'), ['q' => $query]);

        if (! $response->successful()) {
            throw new RetailerUnavailable('Amazon search returned HTTP '.$response->status().'.');
        }

        return $this->parseCandidates($this->retailer(), $response->json() ?? []);
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        if (! (bool) config('deals.amazon.enabled', false) || (string) config('deals.amazon.price_url', '') === '') {
            return null;
        }

        $response = Http::timeout((int) config('deals.request_timeout', 15))
            ->acceptJson()
            ->get(rtrim((string) config('deals.amazon.price_url'), '/').'/'.$listing->external_id);

        if (! $response->successful()) {
            throw new RetailerUnavailable('Amazon price returned HTTP '.$response->status().'.');
        }

        return $this->parsePrice($response->json() ?? []);
    }
}
