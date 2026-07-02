<?php

namespace Modules\Recipes\View\ViewModels;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Recipes\Data\MatchedOffer;
use Modules\Recipes\Data\RecipeDealsMatch;
use Modules\Recipes\Http\Resources\OfferResource;
use Modules\Recipes\Http\Resources\RecipeResource;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Models\RecipeRun;
use Modules\Recipes\Services\OfferAggregator;
use Modules\Recipes\Services\RecipeDealsMatcher;

class RecipesViewModel
{
    public function __construct(
        private readonly OfferAggregator $offerAggregator,
        private readonly RecipeDealsMatcher $dealsMatcher,
    ) {}

    public function state(?string $weekKey = null): array
    {
        $weekKey ??= $this->offerAggregator->weekKey();
        $run = RecipeRun::query()->where('week_key', $weekKey)->first();
        $recipes = Recipe::query()
            ->where('week_key', $weekKey)
            ->orderBy('created_at')
            ->get();
        $offers = GroceryOffer::query()
            ->where('week_key', $weekKey)
            ->orderBy('store')
            ->orderBy('product_name')
            ->get();
        $storesFetched = $run?->stores_fetched ?? $offers->pluck('store')->unique()->values()->all();

        return [
            'week_key' => $weekKey,
            'generated_at' => $run?->generated_at?->toIso8601String(),
            'is_fallback' => (bool) ($run?->ai_unavailable ?? false),
            'stores_fetched' => $storesFetched,
            'stores_failed' => $run?->stores_failed ?? [],
            'recipes' => RecipeResource::collection($recipes)->resolve(),
            'offers' => OfferResource::collection($offers)->resolve(),
            'recipe_deals' => $this->recipeDeals($recipes, $offers, $weekKey),
        ];
    }

    /**
     * Per-recipe matched offers + savings, keyed by recipe id (recipes without matches are omitted). Cached briefly since matching is expensive.
     *
     * @param  Collection<int, Recipe>  $recipes
     * @param  Collection<int, GroceryOffer>  $offers
     */
    private function recipeDeals(Collection $recipes, Collection $offers, string $weekKey): array
    {
        if ($recipes->isEmpty() || $offers->isEmpty()) {
            return [];
        }

        $cacheKey = "recipes.deals.{$weekKey}.{$recipes->count()}.{$offers->count()}";

        return Cache::remember($cacheKey, 300, function () use ($recipes, $offers): array {
            return $this->dealsMatcher->match($recipes, $offers)
                ->filter(fn (RecipeDealsMatch $match): bool => $match->hasMatches())
                ->map(fn (RecipeDealsMatch $match): array => [
                    'total_savings' => $match->totalSavings,
                    'matches' => collect($match->matchedOffers)->map(fn (MatchedOffer $matchedOffer): array => [
                        'ingredient' => $matchedOffer->ingredientName,
                        'offer' => $matchedOffer->offerProductName,
                        'store' => $matchedOffer->store,
                        'savings' => $matchedOffer->savings,
                    ])->all(),
                ])
                ->all();
        });
    }
}
