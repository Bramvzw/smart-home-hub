<?php

namespace Modules\Recipes\View\ViewModels;

use Modules\Recipes\Http\Resources\OfferResource;
use Modules\Recipes\Http\Resources\RecipeResource;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Models\RecipeRun;
use Modules\Recipes\Services\OfferAggregator;

class RecipesViewModel
{
    public function __construct(
        private readonly OfferAggregator $offerAggregator,
    ) {
    }

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
        ];
    }
}
