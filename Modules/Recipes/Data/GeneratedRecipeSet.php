<?php

namespace Modules\Recipes\Data;

final readonly class GeneratedRecipeSet
{
    /**
     * @param  list<array<string, mixed>>  $recipes
     */
    public function __construct(
        public array $recipes,
        public string $model,
    ) {
    }
}
