<?php

namespace Modules\Recipes\Tests\Unit;

use Modules\Recipes\Services\PrismRecipeTextGenerator;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\Text\Request;
use Tests\TestCase;

class PrismRecipeTextGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.anthropic.api_key' => 'test-key']);
    }

    public function test_prompts_ask_for_dutch_recipes_by_default(): void
    {
        $fake = Prism::fake([$this->recipeResponse()]);

        app(PrismRecipeTextGenerator::class)->generate([], 2, 2);

        $fake->assertRequest(function (array $requests): void {
            /** @var Request $request */
            $request = $requests[0];

            $this->assertStringContainsString('in het Nederlands', $request->systemPrompts()[0]->content);
            $this->assertStringContainsString('recepten in het Nederlands', (string) $request->prompt());
        });
    }

    public function test_prompts_follow_the_configured_language(): void
    {
        config(['recipes.language' => 'en']);

        $fake = Prism::fake([$this->recipeResponse()]);

        app(PrismRecipeTextGenerator::class)->generate([], 2, 2);

        $fake->assertRequest(function (array $requests): void {
            /** @var Request $request */
            $request = $requests[0];

            $this->assertStringContainsString('in het Engels', $request->systemPrompts()[0]->content);
            $this->assertStringContainsString('recepten in het Engels', (string) $request->prompt());
        });
    }

    private function recipeResponse(): \Prism\Prism\Text\Response
    {
        return TextResponseFake::make()->withText(json_encode([
            'recipes' => [[
                'title' => 'Test recipe',
                'description' => 'A test recipe.',
                'servings' => 2,
                'time_minutes' => 20,
                'estimated_cost' => 5.00,
                'ingredients' => [],
                'steps' => ['Cook.'],
                'shopping_list' => [],
            ]],
        ], JSON_THROW_ON_ERROR));
    }
}
