<?php

namespace Modules\Entertainment\Services\Tmdb;

use Illuminate\Support\Facades\Http;
use Modules\Entertainment\Exceptions\EntertainmentSourceUnavailable;

class TmdbClient
{
    public function nowPlayingNl(): array
    {
        return $this->get('/movie/now_playing', ['region' => (string) config('entertainment.region', 'NL')])['results'] ?? [];
    }

    public function watchProviders(int $tmdbId): array
    {
        return data_get($this->get("/movie/{$tmdbId}/watch/providers"), 'results.'.config('entertainment.region', 'NL'), []);
    }

    public function details(int $tmdbId): array
    {
        return $this->get("/movie/{$tmdbId}");
    }

    private function get(string $path, array $query = []): array
    {
        $key = (string) config('entertainment.tmdb.api_key', '');

        if ($key === '') {
            throw new EntertainmentSourceUnavailable('TMDB API key is not configured.');
        }

        $response = Http::timeout((int) config('entertainment.tmdb.timeout', 10))
            ->acceptJson()
            ->withToken($key)
            ->get('https://api.themoviedb.org/3'.$path, array_merge(['language' => 'nl-NL'], $query));

        if (! $response->successful()) {
            throw new EntertainmentSourceUnavailable('TMDB returned HTTP '.$response->status().'.');
        }

        return $response->json() ?? [];
    }
}
