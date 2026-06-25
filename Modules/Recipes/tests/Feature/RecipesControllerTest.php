<?php

namespace Modules\Recipes\Tests\Feature;

use App\Services\Ntfy\HubNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Recipes\Actions\FetchOffers;
use Modules\Recipes\Actions\GenerateRecipes;
use Modules\Recipes\Contracts\RecipeTextGenerator;
use Modules\Recipes\Data\GeneratedRecipeSet;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Models\RecipeRun;
use RuntimeException;
use Tests\TestCase;

class RecipesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-26 18:00:00', 'UTC'));
        config([
            'recipes.recipe_count' => 4,
            'recipes.servings' => 2,
            'recipes.sources.ah.anonymous_token_url' => 'https://example.com/ah-token',
            'recipes.sources.ah.offers_url' => 'https://example.com/ah-offers',
            'recipes.sources.lidl.offers_url' => 'https://example.com/lidl-offers',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_fetch_offers_stores_available_store_and_records_failure(): void
    {
        Http::fake([
            'https://example.com/ah-token' => Http::response(['access_token' => 'token']),
            'https://example.com/ah-offers*' => Http::response($this->fixture('ah-offers.json')),
            'https://example.com/lidl-offers' => Http::response('Nope', 500),
        ]);

        $result = app(FetchOffers::class)();

        $this->assertSame(['ah'], $result->storesFetched);
        $this->assertSame(['lidl'], $result->storesFailed);
        $this->assertDatabaseHas('grocery_offers', [
            'week_key' => '2026-W26',
            'store' => 'ah',
            'product_name' => 'Kipfilet',
        ]);
        $this->assertDatabaseHas('recipe_runs', [
            'week_key' => '2026-W26',
        ]);
    }

    public function test_generate_recipes_stores_recipes_idempotently_and_sends_ntfy(): void
    {
        $this->offer('ah', 'Kipfilet');
        $this->offer('lidl', 'Paprika mix');
        $notifier = new FakeRecipesNotifier;
        $this->app->instance(HubNotifier::class, $notifier);
        $this->app->instance(RecipeTextGenerator::class, new FakeRecipeTextGenerator);

        app(GenerateRecipes::class)(weekKey: '2026-W26', push: true);
        app(GenerateRecipes::class)(weekKey: '2026-W26', push: true);

        $this->assertDatabaseCount('recipes', 4);
        $this->assertDatabaseHas('recipes', [
            'week_key' => '2026-W26',
            'title' => 'Snelle kip-teriyaki',
            'model' => 'fake-claude',
        ]);
        $this->assertFalse(RecipeRun::query()->where('week_key', '2026-W26')->firstOrFail()->ai_unavailable);
        $this->assertCount(2, $notifier->sent);
        $this->assertStringContainsString('Snelle kip-teriyaki', $notifier->sent[0]['message']);
    }

    public function test_ai_fallback_keeps_offers_flags_week_and_pushes_offer_summary(): void
    {
        $this->offer('ah', 'Kipfilet');
        $notifier = new FakeRecipesNotifier;
        $this->app->instance(HubNotifier::class, $notifier);
        $this->app->instance(RecipeTextGenerator::class, new FailingRecipeTextGenerator);

        $recipes = app(GenerateRecipes::class)(weekKey: '2026-W26', push: true);

        $this->assertSame([], $recipes);
        $this->assertDatabaseCount('recipes', 0);
        $this->assertTrue(RecipeRun::query()->where('week_key', '2026-W26')->firstOrFail()->ai_unavailable);
        $this->assertCount(1, $notifier->sent);
        $this->assertStringContainsString('Geen AI-recepten beschikbaar', $notifier->sent[0]['message']);
        $this->assertStringContainsString('Kipfilet', $notifier->sent[0]['message']);
    }

    public function test_recipes_endpoints_return_contracts(): void
    {
        RecipeRun::query()->create([
            'week_key' => '2026-W26',
            'stores_fetched' => ['ah', 'lidl'],
            'stores_failed' => [],
            'ai_unavailable' => false,
            'generated_at' => CarbonImmutable::parse('2026-06-26 18:05:00', 'UTC'),
        ]);
        $this->offer('ah', 'Kipfilet');
        $recipe = $this->recipe();

        $this->getJson(route('recipes.index'))
            ->assertOk()
            ->assertJsonPath('week_key', '2026-W26')
            ->assertJsonPath('is_fallback', false)
            ->assertJsonPath('stores_fetched', ['ah', 'lidl'])
            ->assertJsonPath('recipes.0.title', 'Snelle kip-teriyaki')
            ->assertJsonPath('recipes.0.on_offer_ingredients', ['Kipfilet (AH)'])
            ->assertJsonStructure([
                'week_key',
                'generated_at',
                'is_fallback',
                'stores_fetched',
                'stores_failed',
                'recipes' => [
                    [
                        'id',
                        'title',
                        'description',
                        'servings',
                        'time_minutes',
                        'estimated_cost',
                        'on_offer_ingredients',
                    ],
                ],
                'offers',
            ]);

        $this->getJson(route('recipes.show', $recipe))
            ->assertOk()
            ->assertJsonPath('shopping_list.0.name', 'Kipfilet')
            ->assertJsonPath('steps.0', 'Bak de kip.');

        $this->getJson(route('recipes.offers.index'))
            ->assertOk()
            ->assertJsonPath('offers.0.product_name', 'Kipfilet');
    }

    public function test_index_renders_html_with_recipe_overview(): void
    {
        $this->withoutVite();

        RecipeRun::query()->create([
            'week_key' => '2026-W26',
            'stores_fetched' => ['ah', 'lidl'],
            'stores_failed' => [],
            'ai_unavailable' => false,
            'generated_at' => CarbonImmutable::parse('2026-06-26 18:05:00', 'UTC'),
        ]);
        $this->offer('ah', 'Kipfilet');
        $this->recipe();

        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee('Recepten')
            ->assertSee('Snelle kip-teriyaki')
            ->assertSee('Albert Heijn');
    }

    public function test_manual_generate_endpoint_reuses_stored_offers_and_pushes(): void
    {
        $this->offer('ah', 'Kipfilet');
        $notifier = new FakeRecipesNotifier;
        $this->app->instance(HubNotifier::class, $notifier);
        $this->app->instance(RecipeTextGenerator::class, new FakeRecipeTextGenerator);

        $this->postJson(route('recipes.generate'))
            ->assertOk()
            ->assertJsonPath('week_key', '2026-W26')
            ->assertJsonPath('recipes.0.title', 'Snelle kip-teriyaki');

        $this->assertDatabaseCount('recipes', 4);
        $this->assertDatabaseCount('grocery_offers', 1);
        $this->assertCount(1, $notifier->sent);
    }

    private function offer(string $store, string $name): GroceryOffer
    {
        return GroceryOffer::query()->create([
            'store' => $store,
            'external_id' => $store.'-'.$name,
            'product_name' => $name,
            'category' => 'Test',
            'normal_price' => 5.99,
            'offer_price' => 3.99,
            'discount_label' => '35% korting',
            'unit' => '300 g',
            'week_key' => '2026-W26',
            'fetched_at' => CarbonImmutable::now(),
        ]);
    }

    private function recipe(): Recipe
    {
        return Recipe::query()->create([
            'week_key' => '2026-W26',
            'title' => 'Snelle kip-teriyaki',
            'description' => 'Simpel en snel.',
            'servings' => 2,
            'time_minutes' => 25,
            'estimated_cost' => 6.40,
            'ingredients' => [
                ['name' => 'Kipfilet', 'amount' => '300 g', 'on_offer' => true, 'store' => 'ah'],
            ],
            'steps' => ['Bak de kip.'],
            'shopping_list' => [
                ['name' => 'Kipfilet', 'amount' => '300 g', 'on_offer' => true, 'store' => 'ah'],
            ],
            'model' => 'fake-claude',
        ]);
    }

    private function fixture(string $file): string
    {
        return file_get_contents(__DIR__."/../Fixtures/{$file}");
    }
}

class FakeRecipeTextGenerator implements RecipeTextGenerator
{
    public function generate(array $offers, int $count, int $servings): GeneratedRecipeSet
    {
        $recipes = [];

        for ($i = 0; $i < $count; $i++) {
            $recipes[] = [
                'title' => $i === 0 ? 'Snelle kip-teriyaki' : 'Recept '.($i + 1),
                'description' => 'Simpel en snel.',
                'servings' => $servings,
                'time_minutes' => 25,
                'estimated_cost' => 6.40,
                'ingredients' => [
                    ['name' => 'Kipfilet', 'amount' => '300 g', 'on_offer' => true, 'store' => 'ah'],
                ],
                'steps' => ['Bak de kip.'],
                'shopping_list' => [
                    ['name' => 'Kipfilet', 'amount' => '300 g', 'on_offer' => true, 'store' => 'ah'],
                ],
            ];
        }

        return new GeneratedRecipeSet($recipes, 'fake-claude');
    }
}

class FailingRecipeTextGenerator implements RecipeTextGenerator
{
    public function generate(array $offers, int $count, int $servings): GeneratedRecipeSet
    {
        throw new RuntimeException('AI down');
    }
}

class FakeRecipesNotifier extends HubNotifier
{
    public array $sent = [];

    public function __construct()
    {
        parent::__construct('https://ntfy.sh', 'topic', '', 10);
    }

    public function send(string $title, string $message): void
    {
        $this->sent[] = compact('title', 'message');
    }
}
