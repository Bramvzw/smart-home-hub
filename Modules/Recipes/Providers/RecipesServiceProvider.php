<?php

namespace Modules\Recipes\Providers;

use App\Providers\ModuleServiceProvider;
use App\Support\Health\ModuleHealth;
use Illuminate\Support\Facades\Schema;
use Modules\Recipes\Contracts\RecipeTextGenerator;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Models\RecipeRun;
use Modules\Recipes\Services\AlbertHeijnOfferProvider;
use Modules\Recipes\Services\LidlOfferProvider;
use Modules\Recipes\Services\OfferAggregator;
use Modules\Recipes\Services\PrismRecipeTextGenerator;

class RecipesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Recipes';

    protected string $nameLower = 'recipes';

    public function register(): void
    {
        parent::register();

        $this->app->bind(RecipeTextGenerator::class, PrismRecipeTextGenerator::class);
        $this->app->tag([AlbertHeijnOfferProvider::class, LidlOfferProvider::class], 'recipes.offer_provider');
        $this->app->bind(OfferAggregator::class, fn ($app) => new OfferAggregator($app->tagged('recipes.offer_provider')));
    }

    public function getModuleName(): string
    {
        return 'Recipes';
    }

    public function getModuleSlug(): string
    {
        return 'recipes';
    }

    public function getNavigation(): array
    {
        return [
            ['label' => 'Recepten', 'route' => 'recipes.index', 'icon' => 'recipes'],
        ];
    }

    public function health(): ModuleHealth
    {
        return ModuleHealth::require([
            'HUB_AI_ANTHROPIC_API_KEY' => config('ai.anthropic.api_key'),
        ]);
    }

    public function getDashboardWidget(): ?string
    {
        if (! Schema::hasTable('recipes') || ! Schema::hasTable('recipe_runs')) {
            return null;
        }

        $weekKey = app(OfferAggregator::class)->weekKey();
        $run = RecipeRun::query()->where('week_key', $weekKey)->first();
        $count = Recipe::query()->where('week_key', $weekKey)->count();

        if ($run?->ai_unavailable) {
            return 'Offers ready, AI unavailable';
        }

        if ($count === 0) {
            return 'No recipes this week';
        }

        return $count === 1 ? '1 recipe this week' : "{$count} recipes this week";
    }
}
