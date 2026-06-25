<?php

namespace Modules\Deals\Services\Retailers;

use Illuminate\Support\Facades\Http;
use Modules\Deals\Contracts\RetailerAdapter;
use Modules\Deals\Data\ListingCandidate;
use Modules\Deals\Exceptions\RetailerUnavailable;
use Modules\Deals\Models\ProductListing;
use Modules\Deals\Services\Retailers\Concerns\ParsesRetailerPayloads;
use Throwable;

class BolAdapter implements RetailerAdapter
{
    use ParsesRetailerPayloads;

    public function retailer(): string
    {
        return 'bol';
    }

    public function search(string $query): array
    {
        if (! (bool) config('deals.bol.enabled', true)) {
            return [];
        }

        try {
            $response = Http::timeout((int) config('deals.request_timeout', 15))
                ->acceptJson()
                ->withToken($this->token())
                ->get((string) config('deals.bol.search_url'), ['search-term' => $query, 'q' => $query]);
        } catch (Throwable $exception) {
            throw new RetailerUnavailable('bol.com search unavailable.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RetailerUnavailable('bol.com search returned HTTP '.$response->status().'.');
        }

        return $this->parseCandidates($this->retailer(), $response->json() ?? []);
    }

    public function fetchPrice(ProductListing $listing): ?float
    {
        try {
            $response = Http::timeout((int) config('deals.request_timeout', 15))
                ->acceptJson()
                ->withToken($this->token())
                ->get(rtrim((string) config('deals.bol.price_url'), '/').'/'.$listing->external_id);
        } catch (Throwable $exception) {
            throw new RetailerUnavailable('bol.com price unavailable.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RetailerUnavailable('bol.com price returned HTTP '.$response->status().'.');
        }

        return $this->parsePrice($response->json() ?? []);
    }

    private function token(): string
    {
        $id = (string) config('deals.bol.client_id', '');
        $secret = (string) config('deals.bol.client_secret', '');

        if ($id === '' || $secret === '') {
            throw new RetailerUnavailable('bol.com credentials are not configured.');
        }

        $response = Http::timeout((int) config('deals.request_timeout', 15))
            ->asForm()
            ->withBasicAuth($id, $secret)
            ->post((string) config('deals.bol.token_url'), ['grant_type' => 'client_credentials']);

        if (! $response->successful()) {
            throw new RetailerUnavailable('bol.com token returned HTTP '.$response->status().'.');
        }

        $token = data_get($response->json(), 'access_token');

        if (! is_string($token) || trim($token) === '') {
            throw new RetailerUnavailable('bol.com token response is missing access_token.');
        }

        return $token;
    }
}
