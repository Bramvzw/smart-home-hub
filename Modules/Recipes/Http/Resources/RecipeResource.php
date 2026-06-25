<?php

namespace Modules\Recipes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'week_key' => $this->week_key,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'servings' => $this->servings,
            'time_minutes' => $this->time_minutes,
            'estimated_cost' => $this->estimated_cost !== null ? (float) $this->estimated_cost : null,
            'ingredients' => $this->ingredients ?? [],
            'steps' => $this->steps ?? [],
            'shopping_list' => $this->shopping_list ?? [],
            'on_offer_ingredients' => $this->onOfferIngredients(),
            'model' => $this->model,
            'is_fallback' => $this->is_fallback,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function onOfferIngredients(): array
    {
        return collect($this->ingredients ?? [])
            ->filter(fn (array $ingredient): bool => (bool) ($ingredient['on_offer'] ?? false))
            ->map(function (array $ingredient): string {
                $store = $ingredient['store'] ?? null;

                return trim((string) ($ingredient['name'] ?? '')).($store ? ' ('.mb_strtoupper((string) $store).')' : '');
            })
            ->filter()
            ->values()
            ->all();
    }
}
