<?php

namespace Modules\Recipes\Contracts;

use Modules\Recipes\Data\GeneratedRecipeSet;

interface RecipeTextGenerator
{
    /**
     * @param  list<array<string, mixed>>  $offers
     */
    public function generate(array $offers, int $count, int $servings): GeneratedRecipeSet;
}
