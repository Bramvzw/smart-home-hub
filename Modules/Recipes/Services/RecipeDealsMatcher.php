<?php

namespace Modules\Recipes\Services;

use Illuminate\Support\Collection;
use Modules\Recipes\Data\MatchedOffer;
use Modules\Recipes\Data\RecipeDealsMatch;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;

class RecipeDealsMatcher
{
    /**
     * Matches recipe ingredients against grocery offers, case-insensitively
     * at word level (e.g. ingredient "kipfilet" matches offer "Kipfilet 500g").
     *
     * @param  Collection<int, Recipe>  $recipes
     * @param  Collection<int, GroceryOffer>  $offers
     * @return Collection<int, RecipeDealsMatch> keyed by recipe id
     */
    public function match(Collection $recipes, Collection $offers): Collection
    {
        return $recipes->mapWithKeys(function (Recipe $recipe) use ($offers): array {
            $matchedOffers = [];
            $totalSavings = 0.0;

            foreach (collect($recipe->ingredients ?? [])->pluck('name')->filter() as $ingredientName) {
                $offer = $this->findMatch((string) $ingredientName, $offers);

                if (! $offer) {
                    continue;
                }

                $normalPrice = $offer->normal_price !== null ? (float) $offer->normal_price : null;
                $offerPrice = $offer->offer_price !== null ? (float) $offer->offer_price : null;
                $savings = ($normalPrice !== null && $offerPrice !== null && $normalPrice > $offerPrice)
                    ? round($normalPrice - $offerPrice, 2)
                    : null;

                $matchedOffers[] = new MatchedOffer(
                    ingredientName: (string) $ingredientName,
                    offerProductName: $offer->product_name,
                    store: $offer->store,
                    normalPrice: $normalPrice,
                    offerPrice: $offerPrice,
                    savings: $savings,
                );

                $totalSavings += $savings ?? 0.0;
            }

            return [$recipe->id => new RecipeDealsMatch($recipe->id, $matchedOffers, round($totalSavings, 2))];
        });
    }

    private function findMatch(string $ingredientName, Collection $offers): ?GroceryOffer
    {
        $ingredientWords = $this->words($ingredientName);

        if ($ingredientWords === []) {
            return null;
        }

        return $offers->first(
            fn (GroceryOffer $offer): bool => array_intersect($ingredientWords, $this->words((string) $offer->product_name)) !== []
        );
    }

    /**
     * @return list<string>
     */
    private function words(string $value): array
    {
        $normalized = mb_strtolower((string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $value));

        return array_values(array_filter(
            explode(' ', $normalized),
            fn (string $word): bool => mb_strlen($word) > 2
        ));
    }
}
