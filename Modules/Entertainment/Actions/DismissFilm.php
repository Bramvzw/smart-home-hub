<?php

namespace Modules\Entertainment\Actions;

use Modules\Entertainment\Models\FilmRecommendation;

class DismissFilm
{
    public function __invoke(FilmRecommendation $film): FilmRecommendation
    {
        $film->update(['dismissed' => true]);

        return $film->fresh();
    }
}
