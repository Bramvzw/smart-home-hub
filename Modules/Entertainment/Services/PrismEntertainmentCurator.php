<?php

namespace Modules\Entertainment\Services;

use Illuminate\Support\Collection;
use Modules\Entertainment\Contracts\EntertainmentCurator;
use Modules\Entertainment\Data\FilmPick;
use Modules\Entertainment\Models\Concert;
use Modules\Entertainment\Models\TasteProfile;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

class PrismEntertainmentCurator implements EntertainmentCurator
{
    public function curateFilms(array $candidates, TasteProfile $profile, Collection $feedback): array
    {
        if ((string) config('ai.anthropic.api_key', '') === '') {
            return collect($candidates)->take(10)->values()->map(fn (array $movie, int $i): FilmPick => new FilmPick((int) $movie['tmdb_id'], 'Past bij je profiel op basis van populariteit en beschikbaarheid.', 100 - $i))->all();
        }

        $payload = json_encode(compact('candidates') + ['profile' => $profile->toArray(), 'feedback' => $feedback->toArray()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $response = Prism::text()
            ->using(Provider::Anthropic, (string) config('entertainment.ai.model', 'claude-sonnet-4-6'), ['api_key' => (string) config('ai.anthropic.api_key')])
            ->withSystemPrompt('Kies films voor een Nederlandse gebruiker. Geef alleen JSON terug: {"picks":[{"tmdb_id":123,"why":"...","score":90}]}')
            ->withPrompt($payload)
            ->withMaxTokens(1200)
            ->asText();
        $json = json_decode(substr($response->text, strpos($response->text, '{'), strrpos($response->text, '}') - strpos($response->text, '{') + 1), true, flags: JSON_THROW_ON_ERROR);

        return collect($json['picks'] ?? [])->map(fn (array $pick): FilmPick => new FilmPick((int) $pick['tmdb_id'], $pick['why'] ?? null, isset($pick['score']) ? (int) $pick['score'] : null))->all();
    }

    public function concertRelevance(Concert $concert, array $followedArtists, TasteProfile $profile): string
    {
        if (collect($followedArtists)->contains(fn (string $artist): bool => mb_strtolower($artist) === mb_strtolower($concert->artist))) {
            return 'followed';
        }

        if ($concert->source === 'hedon') {
            return 'hedon';
        }

        return 'none';
    }
}
