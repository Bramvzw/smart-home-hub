<?php

namespace Modules\Entertainment\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Modules\Entertainment\Contracts\EntertainmentCurator;
use Modules\Entertainment\Models\FilmFeedback;
use Modules\Entertainment\Models\FilmRecommendation;
use Modules\Entertainment\Models\TasteProfile;
use Modules\Entertainment\Services\Tmdb\TmdbClient;

class RefreshFilms
{
    public function __construct(
        private readonly TmdbClient $tmdb,
        private readonly EntertainmentCurator $curator,
    ) {}

    public function __invoke(): int
    {
        $candidates = collect($this->tmdb->nowPlayingNl())
            ->map(function (array $movie): array {
                $availability = ['cinema'];
                $providers = $this->tmdb->watchProviders((int) $movie['id']);
                $providerNames = collect(data_get($providers, 'flatrate', []))->pluck('provider_name')->map(fn ($name) => mb_strtolower((string) $name));

                if ($providerNames->contains(fn (string $name): bool => str_contains($name, 'netflix'))) {
                    $availability[] = 'netflix';
                }

                if ($providerNames->contains(fn (string $name): bool => str_contains($name, 'prime'))) {
                    $availability[] = 'prime';
                }

                return [
                    'tmdb_id' => (int) $movie['id'],
                    'title' => (string) ($movie['title'] ?? $movie['name'] ?? ''),
                    'overview' => $movie['overview'] ?? null,
                    'availability' => array_values(array_unique($availability)),
                    'poster_url' => isset($movie['poster_path']) ? 'https://image.tmdb.org/t/p/w500'.$movie['poster_path'] : null,
                ];
            })
            ->filter(fn (array $movie): bool => $movie['tmdb_id'] > 0 && $movie['title'] !== '')
            ->values();
        $profile = TasteProfile::query()->firstOrCreate([], ['favorite_titles' => [], 'genres' => []]);
        $picks = collect($this->curator->curateFilms($candidates->all(), $profile, FilmFeedback::query()->latest('created_at')->limit(50)->get()));
        $now = CarbonImmutable::now();

        foreach ($candidates as $movie) {
            $pick = $picks->first(fn ($pick) => $pick->tmdbId === $movie['tmdb_id']);

            FilmRecommendation::query()->updateOrCreate(
                ['tmdb_id' => $movie['tmdb_id']],
                array_merge($movie, [
                    'why' => $pick?->why,
                    'score' => $pick?->score,
                    'refreshed_at' => $now,
                ])
            );
        }

        return $candidates->count();
    }
}
