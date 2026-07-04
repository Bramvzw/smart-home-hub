<?php

namespace Modules\Recipes\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Services\OfferAggregator;

class RecipesBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly OfferAggregator $offerAggregator,
    ) {}

    public function key(): string
    {
        return 'recipes';
    }

    public function label(): string
    {
        return 'Recipes';
    }

    public function priority(): int
    {
        return 80;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $weekKey = $this->offerAggregator->weekKey($date);
        $recipes = Recipe::query()
            ->where('week_key', $weekKey)
            ->orderBy('created_at')
            ->get();

        if ($recipes->isEmpty()) {
            return null;
        }

        $titles = $recipes->pluck('title')->all();

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: 'Weekly menu ready: '.count($titles).' recipe'.(count($titles) === 1 ? '' : 's').' — '.implode(', ', $titles),
            data: [
                'week_key' => $weekKey,
                'recipes' => $recipes->map(fn (Recipe $recipe): array => [
                    'title' => $recipe->title,
                    'servings' => $recipe->servings,
                    'time_minutes' => $recipe->time_minutes,
                    'estimated_cost' => $recipe->estimated_cost !== null ? (float) $recipe->estimated_cost : null,
                ])->values()->all(),
            ],
        );
    }
}
