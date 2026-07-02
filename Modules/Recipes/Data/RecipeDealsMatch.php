<?php

namespace Modules\Recipes\Data;

final readonly class RecipeDealsMatch
{
    /**
     * @param  list<MatchedOffer>  $matchedOffers
     */
    public function __construct(
        public int $recipeId,
        public array $matchedOffers,
        public float $totalSavings,
    ) {}

    public function hasMatches(): bool
    {
        return $this->matchedOffers !== [];
    }
}
