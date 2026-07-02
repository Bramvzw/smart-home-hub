<?php

namespace Modules\Recipes\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Recipes\Models\GroceryOffer;
use Modules\Recipes\Models\Recipe;
use Modules\Recipes\Services\RecipeDealsMatcher;
use Tests\TestCase;

class RecipeDealsMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_matches_an_ingredient_to_an_offer_and_computes_savings(): void
    {
        $recipe = $this->recipe([
            ['name' => 'Kipfilet', 'amount' => '300 g', 'on_offer' => true, 'store' => 'ah'],
        ]);
        $this->offer('ah', 'Kipfilet 500g', normalPrice: 5.99, offerPrice: 3.99);

        $result = (new RecipeDealsMatcher)->match(
            Recipe::query()->get(),
            GroceryOffer::query()->get(),
        );

        $match = $result->get($recipe->id);

        $this->assertTrue($match->hasMatches());
        $this->assertSame(2.0, $match->totalSavings);
        $this->assertSame('Kipfilet 500g', $match->matchedOffers[0]->offerProductName);
    }

    public function test_it_is_case_insensitive_at_word_level(): void
    {
        $recipe = $this->recipe([
            ['name' => 'KIPFILET', 'amount' => '300 g', 'on_offer' => false, 'store' => null],
        ]);
        $this->offer('ah', 'kipfilet 500g', normalPrice: 5.99, offerPrice: 3.99);

        $result = (new RecipeDealsMatcher)->match(
            Recipe::query()->get(),
            GroceryOffer::query()->get(),
        );

        $this->assertTrue($result->get($recipe->id)->hasMatches());
    }

    public function test_it_reports_no_matches_when_no_offer_shares_a_word(): void
    {
        $recipe = $this->recipe([
            ['name' => 'Rijst', 'amount' => '250 g', 'on_offer' => false, 'store' => null],
        ]);
        $this->offer('ah', 'Kipfilet 500g', normalPrice: 5.99, offerPrice: 3.99);

        $result = (new RecipeDealsMatcher)->match(
            Recipe::query()->get(),
            GroceryOffer::query()->get(),
        );

        $match = $result->get($recipe->id);

        $this->assertFalse($match->hasMatches());
        $this->assertSame(0.0, $match->totalSavings);
    }

    private function recipe(array $ingredients): Recipe
    {
        return Recipe::query()->create([
            'week_key' => '2026-W27',
            'title' => 'Test recept',
            'description' => 'Test',
            'servings' => 2,
            'time_minutes' => 20,
            'estimated_cost' => 5,
            'ingredients' => $ingredients,
            'steps' => ['Doe iets.'],
            'shopping_list' => $ingredients,
            'model' => 'fake-claude',
        ]);
    }

    private function offer(string $store, string $productName, float $normalPrice, float $offerPrice): GroceryOffer
    {
        return GroceryOffer::query()->create([
            'store' => $store,
            'external_id' => $store.'-'.$productName,
            'product_name' => $productName,
            'category' => 'Test',
            'normal_price' => $normalPrice,
            'offer_price' => $offerPrice,
            'discount_label' => 'Aanbieding',
            'unit' => '500 g',
            'week_key' => '2026-W27',
            'fetched_at' => now(),
        ]);
    }
}
