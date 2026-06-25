<?php

namespace Modules\Recipes\Services;

use Illuminate\Support\Facades\Http;
use Modules\Recipes\Contracts\OfferProvider;
use Modules\Recipes\Data\OfferData;
use Modules\Recipes\Exceptions\OfferSourceUnavailable;
use Modules\Recipes\Services\Concerns\ParsesOfferPayloads;
use Throwable;

class LidlOfferProvider implements OfferProvider
{
    use ParsesOfferPayloads;

    public function store(): string
    {
        return 'lidl';
    }

    /**
     * @return list<OfferData>
     */
    public function fetch(): array
    {
        try {
            $response = Http::timeout((int) config('recipes.request_timeout', 15))
                ->acceptJson()
                ->get((string) config('recipes.sources.lidl.offers_url'));
        } catch (Throwable $exception) {
            throw new OfferSourceUnavailable('Lidl offers could not be fetched.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new OfferSourceUnavailable('Lidl offers responded with HTTP '.$response->status().'.');
        }

        return $this->parseOffers($this->store(), $response->json() ?? []);
    }
}
