<?php

namespace Modules\Entertainment\Services\Music;

use Carbon\CarbonImmutable;
use Modules\Spotify\Services\SpotifyApiClient;
use Modules\Spotify\Services\SpotifyLibraryService;

class SpotifyReleasesService
{
    public function __construct(
        private readonly SpotifyApiClient $spotify,
        private readonly SpotifyLibraryService $library,
    ) {}

    public function followedArtists(): array
    {
        $data = $this->spotify->request('GET', '/me/following?type=artist&limit=50');

        return collect(data_get($data, 'artists.items', []))
            ->map(fn (array $artist): array => ['id' => $artist['id'] ?? null, 'name' => $artist['name'] ?? null])
            ->filter(fn (array $artist): bool => $artist['id'] && $artist['name'])
            ->values()
            ->all();
    }

    public function recentReleasesFor(array $artists, CarbonImmutable $since): array
    {
        $releases = [];

        foreach ($artists as $artist) {
            $data = $this->spotify->request('GET', '/artists/'.$artist['id'].'/albums?include_groups=album,single&market=NL&limit=20');

            foreach (data_get($data, 'items', []) as $item) {
                $date = CarbonImmutable::parse($item['release_date'] ?? '1900-01-01');

                if ($date->lt($since)) {
                    continue;
                }

                $type = ($item['album_type'] ?? 'album') === 'single' ? 'single' : 'album';
                $releases[] = [
                    'spotify_id' => $item['id'],
                    'artist' => $artist['name'],
                    'title' => $item['name'],
                    'type' => $type,
                    'release_date' => $date->toDateString(),
                    'url' => data_get($item, 'external_urls.spotify'),
                    'image_url' => data_get($item, 'images.0.url'),
                ];
            }
        }

        return $releases;
    }

    /** Saved status per spotify_id; ids missing from the map could not be resolved and should be left untouched. */
    public function checkSaved(array $spotifyIds): array
    {
        $status = [];

        foreach (array_chunk($spotifyIds, 20) as $chunk) {
            $response = $this->library->checkSavedAlbums($chunk);

            if (isset($response['error'])) {
                continue;
            }

            foreach ($chunk as $index => $spotifyId) {
                if (array_key_exists($index, $response)) {
                    $status[$spotifyId] = (bool) $response[$index];
                }
            }
        }

        return $status;
    }
}
