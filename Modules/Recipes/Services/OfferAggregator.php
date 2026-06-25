<?php

namespace Modules\Recipes\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Modules\Recipes\Contracts\OfferProvider;
use Modules\Recipes\Data\OfferData;
use Modules\Recipes\Data\OfferFetchResult;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\RecipeRun;
use Throwable;

class OfferAggregator
{
    /**
     * @param  iterable<OfferProvider>  $providers
     */
    public function __construct(
        private readonly iterable $providers,
    ) {
    }

    public function fetch(?CarbonInterface $date = null): OfferFetchResult
    {
        $date = CarbonImmutable::instance($date ?? CarbonImmutable::now((string) config('app.timezone', 'UTC')));
        $weekKey = $this->weekKey($date);
        $stores = (array) config('recipes.stores', ['ah', 'lidl']);
        $storesFetched = [];
        $storesFailed = [];
        $stored = 0;

        foreach ($this->providers as $provider) {
            if (! in_array($provider->store(), $stores, true)) {
                continue;
            }

            try {
                $offers = $provider->fetch();
                $this->storeOffers($offers, $weekKey, $date);
                $stored += count($offers);
                $storesFetched[] = $provider->store();
            } catch (Throwable $exception) {
                Log::warning('Recipe offer source failed.', [
                    'store' => $provider->store(),
                    'message' => $exception->getMessage(),
                ]);

                $storesFailed[] = $provider->store();
            }
        }

        RecipeRun::query()->updateOrCreate(
            ['week_key' => $weekKey],
            [
                'stores_fetched' => array_values(array_unique($storesFetched)),
                'stores_failed' => array_values(array_unique($storesFailed)),
            ]
        );

        return new OfferFetchResult(
            weekKey: $weekKey,
            storesFetched: array_values(array_unique($storesFetched)),
            storesFailed: array_values(array_unique($storesFailed)),
            offersStored: $stored,
        );
    }

    public function weekKey(?CarbonInterface $date = null): string
    {
        $date = CarbonImmutable::instance($date ?? CarbonImmutable::now((string) config('app.timezone', 'UTC')));

        return $date->format('o-\WW');
    }

    /**
     * @param  list<OfferData>  $offers
     */
    private function storeOffers(array $offers, string $weekKey, CarbonInterface $fetchedAt): void
    {
        foreach ($offers as $offer) {
            $attributes = $offer->toAttributes($weekKey, $fetchedAt);
            $externalId = $offer->externalId ?: $this->fingerprint($offer);

            GroceryOffer::query()->updateOrCreate(
                [
                    'store' => $offer->store,
                    'external_id' => $externalId,
                    'week_key' => $weekKey,
                ],
                array_merge($attributes, ['external_id' => $externalId])
            );
        }
    }

    private function fingerprint(OfferData $offer): string
    {
        return 'generated:'.sha1(implode('|', [
            $offer->store,
            mb_strtolower($offer->productName),
            $offer->discountLabel ?? '',
            $offer->offerPrice ?? '',
            $offer->validTo?->toDateString() ?? '',
        ]));
    }
}
