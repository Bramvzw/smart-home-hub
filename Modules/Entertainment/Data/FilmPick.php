<?php

namespace Modules\Entertainment\Data;

final readonly class FilmPick
{
    public function __construct(
        public int $tmdbId,
        public ?string $why,
        public ?int $score,
    ) {
    }
}
