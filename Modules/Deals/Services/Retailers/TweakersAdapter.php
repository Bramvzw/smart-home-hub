<?php

namespace Modules\Deals\Services\Retailers;

use Illuminate\Support\Facades\Http;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Exceptions\RetailerUnavailable;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Services\Retailers\Concerns\ParsesRetailerPayloads;

class TweakersAdapter implements RetailerAdapter
{
    use ParsesRetailerPayloads;

    public function retailer(): string
    {
        return 'tweakers';
    }

    public function search(string $query): array
    {
        if (! (bool) config('deals.tweakers.enabled', true)) {
            return [];
        }

        $url = (string) config('deals.tweakers.search_url', '');
        $response = str_ends_with($url, '=')
            ? Http::timeout((int) config('deals.request_timeout', 15))->acceptJson()->get($url.urlencode($query))
            : Http::timeout((int) config('deals.request_timeout', 15))->acceptJson()->get($url, ['q' => $query]);

        if (! $response->successful()) {
            throw new RetailerUnavailable('Tweakers search returned HTTP '.$response->status().'.');
        }

        return $this->parseCandidates($this->retailer(), $response->json() ?? []);
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        $url = (string) config('deals.tweakers.price_url', '');
        $target = $url !== '' ? rtrim($url, '/').'/'.$listing->external_id : $listing->url;
        $response = Http::timeout((int) config('deals.request_timeout', 15))->acceptJson()->get($target);

        if (! $response->successful()) {
            throw new RetailerUnavailable('Tweakers price returned HTTP '.$response->status().'.');
        }

        return $this->parsePrice($response->json() ?? []);
    }
}
