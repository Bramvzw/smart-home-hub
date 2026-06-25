<?php

namespace Modules\Recipes\Services;

use Illuminate\Support\Facades\Http;
use Modules\Recipes\Contracts\OfferProvider;
use Modules\Recipes\Data\OfferData;
use Modules\Recipes\Exceptions\OfferSourceUnavailable;
use Modules\Recipes\Services\Concerns\ParsesOfferPayloads;
use Throwable;

class AlbertHeijnOfferProvider implements OfferProvider
{
    use ParsesOfferPayloads;

    public function store(): string
    {
        return 'ah';
    }

    /**
     * @return list<OfferData>
     */
    public function fetch(): array
    {
        try {
            $token = $this->anonymousToken();
            $response = Http::timeout((int) config('recipes.request_timeout', 15))
                ->acceptJson()
                ->withToken($token)
                ->get((string) config('recipes.sources.ah.offers_url'), [
                    'path' => 'bonus',
                ]);
        } catch (Throwable $exception) {
            throw new OfferSourceUnavailable('Albert Heijn offers could not be fetched.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new OfferSourceUnavailable('Albert Heijn offers responded with HTTP '.$response->status().'.');
        }

        return $this->parseOffers($this->store(), $response->json() ?? []);
    }

    private function anonymousToken(): string
    {
        $response = Http::timeout((int) config('recipes.request_timeout', 15))
            ->acceptJson()
            ->asJson()
            ->post((string) config('recipes.sources.ah.anonymous_token_url'), [
                'clientId' => (string) config('recipes.sources.ah.client_id', 'appie'),
            ]);

        if (! $response->successful()) {
            throw new OfferSourceUnavailable('Albert Heijn anonymous token responded with HTTP '.$response->status().'.');
        }

        $token = data_get($response->json(), 'access_token')
            ?? data_get($response->json(), 'accessToken')
            ?? data_get($response->json(), 'token');

        if (! is_string($token) || trim($token) === '') {
            throw new OfferSourceUnavailable('Albert Heijn anonymous token was missing.');
        }

        return $token;
    }
}
