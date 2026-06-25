<?php

namespace Modules\Entertainment\Actions;

use Carbon\CarbonImmutable;
use Modules\Entertainment\Models\FilmFeedback;
use Modules\Entertainment\Models\FilmRecommendation;

class RecordFilmFeedback
{
    public function __invoke(FilmRecommendation $film, string $sentiment): FilmFeedback
    {
        return FilmFeedback::query()->create([
            'tmdb_id' => $film->tmdb_id,
            'title' => $film->title,
            'sentiment' => $sentiment,
            'created_at' => CarbonImmutable::now(),
        ]);
    }
}
