<?php

namespace Modules\Recipes\Data;

final readonly class MatchedOffer
{
    public function __construct(
        public string $ingredientName,
        public string $offerProductName,
        public string $store,
        public ?float $normalPrice,
        public ?float $offerPrice,
        public ?float $savings,
    ) {}
}
