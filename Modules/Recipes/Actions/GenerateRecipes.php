<?php

namespace Modules\Recipes\Actions;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Models\RecipeRun;
use Modules\Recipes\Services\OfferAggregator;
use Modules\Recipes\Services\RecipeGenerator;
use Throwable;

class GenerateRecipes
{
    public function __construct(
        private readonly OfferAggregator $offerAggregator,
        private readonly RecipeGenerator $recipeGenerator,
        private readonly HubNotifier $notifier,
    ) {
    }

    /**
     * @param  string|null  $resolvedWeekKey  Receives the week_key actually used for generation.
     * @return list<Recipe>
     */
    public function __invoke(?string $weekKey = null, bool $push = true, bool $refetchOffers = false, ?string &$resolvedWeekKey = null): array
    {
        $weekKey ??= $this->offerAggregator->weekKey();

        if ($refetchOffers || ! GroceryOffer::query()->where('week_key', $weekKey)->exists()) {
            $result = $this->offerAggregator->fetch();
            $weekKey = $result->weekKey;
        }

        $resolvedWeekKey = $weekKey;

        $offers = GroceryOffer::query()
            ->where('week_key', $weekKey)
            ->orderBy('store')
            ->orderBy('product_name')
            ->get();
        $run = RecipeRun::query()->firstOrCreate(['week_key' => $weekKey]);

        try {
            $recipes = $this->recipeGenerator->generate($offers, $weekKey);
            $run->forceFill([
                'ai_unavailable' => false,
                'generated_at' => $this->now(),
            ])->save();
        } catch (Throwable $exception) {
            Log::warning('Recipe generation failed; keeping offers without recipes.', [
                'week_key' => $weekKey,
                'message' => $exception->getMessage(),
            ]);

            Recipe::query()->where('week_key', $weekKey)->delete();
            $run->forceFill([
                'ai_unavailable' => true,
                'generated_at' => $this->now(),
            ])->save();
            $recipes = [];
        }

        if ($push) {
            $this->push($weekKey, $recipes, $offers->pluck('product_name')->take(8)->all(), $run->fresh());
        }

        return $recipes;
    }

    private function push(string $weekKey, array $recipes, array $offerNames, RecipeRun $run): void
    {
        if ($recipes !== []) {
            $body = "Recepten voor {$weekKey} staan klaar:\n";
            $body .= collect($recipes)
                ->map(fn (Recipe $recipe): string => '- '.$recipe->title)
                ->implode("\n");
            $body .= "\n\nBoodschappenlijsten staan in de hub.";
        } else {
            $body = "Geen AI-recepten beschikbaar voor {$weekKey}.";

            if ($offerNames !== []) {
                $body .= "\n\nWel opgehaalde aanbiedingen:\n".collect($offerNames)
                    ->map(fn (string $name): string => '- '.$name)
                    ->implode("\n");
            }
        }

        if (($run->stores_failed ?? []) !== []) {
            $body .= "\n\nNiet gelukt: ".implode(', ', $run->stores_failed);
        }

        $this->notifier->send('Recepten voor het weekend', $body);
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now((string) config('app.timezone', 'UTC'));
    }
}
