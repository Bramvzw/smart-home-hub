<?php

namespace Modules\Recipes\Services;

use Illuminate\Support\Collection;
use Modules\Recipes\Contracts\RecipeTextGenerator;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use RuntimeException;

class RecipeGenerator
{
    public function __construct(
        private readonly RecipeTextGenerator $generator,
    ) {
    }

    /**
     * @param  Collection<int, GroceryOffer>  $offers
     * @return list<Recipe>
     */
    public function generate(Collection $offers, string $weekKey): array
    {
        if ($offers->isEmpty()) {
            throw new RuntimeException('No offers are available for recipe generation.');
        }

        $count = max(4, min(5, (int) config('recipes.recipe_count', 5)));
        $servings = max(1, min(2, (int) config('recipes.servings', 2)));
        $generated = $this->generator->generate(
            offers: $offers->map(fn (GroceryOffer $offer): array => [
                'store' => $offer->store,
                'product_name' => $offer->product_name,
                'category' => $offer->category,
                'normal_price' => $offer->normal_price ? (float) $offer->normal_price : null,
                'offer_price' => $offer->offer_price ? (float) $offer->offer_price : null,
                'discount_label' => $offer->discount_label,
                'unit' => $offer->unit,
            ])->values()->all(),
            count: $count,
            servings: $servings,
        );

        $recipes = collect($generated->recipes)
            ->take($count)
            ->map(fn (array $recipe): array => $this->normalizeRecipe($recipe, $weekKey, $servings, $generated->model))
            ->filter(fn (array $recipe): bool => $recipe['title'] !== '' && $recipe['ingredients'] !== [] && $recipe['steps'] !== [])
            ->values();

        if ($recipes->isEmpty()) {
            throw new RuntimeException('AI returned no usable recipes.');
        }

        Recipe::query()->where('week_key', $weekKey)->delete();

        return $recipes
            ->map(fn (array $recipe): Recipe => Recipe::query()->create($recipe))
            ->all();
    }

    private function normalizeRecipe(array $recipe, string $weekKey, int $servings, string $model): array
    {
        return [
            'week_key' => $weekKey,
            'title' => trim((string) ($recipe['title'] ?? '')),
            'description' => trim((string) ($recipe['description'] ?? '')) ?: null,
            'servings' => max(1, min(2, (int) ($recipe['servings'] ?? $servings))),
            'time_minutes' => $this->integerOrNull($recipe['time_minutes'] ?? $recipe['timeMinutes'] ?? null),
            'estimated_cost' => $this->floatOrNull($recipe['estimated_cost'] ?? $recipe['estimatedCost'] ?? null),
            'ingredients' => $this->listOfArrays($recipe['ingredients'] ?? []),
            'steps' => $this->listOfStrings($recipe['steps'] ?? []),
            'shopping_list' => $this->listOfArrays($recipe['shopping_list'] ?? $recipe['shoppingList'] ?? []),
            'model' => $model,
            'is_fallback' => false,
        ];
    }

    private function listOfArrays(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'name' => trim((string) ($item['name'] ?? '')),
                'amount' => trim((string) ($item['amount'] ?? '')),
                'on_offer' => (bool) ($item['on_offer'] ?? $item['onOffer'] ?? false),
                'store' => $item['store'] ?? null,
            ])
            ->filter(fn (array $item): bool => $item['name'] !== '')
            ->values()
            ->all();
    }

    private function listOfStrings(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (mixed $item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();
    }

    private function integerOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? max(1, (int) $value) : null;
    }

    private function floatOrNull(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^0-9,.\-]/', '', $value));
        }

        return is_numeric($value) ? round((float) $value, 2) : null;
    }
}
