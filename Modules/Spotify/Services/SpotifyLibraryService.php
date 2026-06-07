<?php

namespace Modules\Spotify\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Spotify\Events\TrackLiked;

class SpotifyLibraryService
{
    public function __construct(
        protected SpotifyApiClient $api,
        protected SpotifyPlaybackService $playback,
    ) {}

    public function getSavedTracks(int $limit = 20): array
    {
        $response = $this->api->request('GET', '/me/tracks', [
            'query' => ['limit' => $limit],
        ]);

        if (!isset($response['items']) || empty($response['items'])) {
            return [];
        }

        return $response['items'];
    }

    public function getUserPlaylists(int $limit = 20, bool $includeLikedSongs = true): array
    {
        $cacheKey = 'spotify_playlists_' . md5($limit . '_' . ($includeLikedSongs ? '1' : '0'));

        return Cache::remember($cacheKey, 300, function () use ($limit, $includeLikedSongs) {
            $response = $this->api->request('GET', '/me/playlists', [
                'query' => ['limit' => $limit],
            ]);

            $playlists = $response['items'] ?? [];

            if ($includeLikedSongs) {
                $savedTracks = $this->getSavedTracks(5);

                if (!empty($savedTracks)) {
                    array_unshift($playlists, [
                        'id' => 'liked-songs',
                        'name' => 'Liked Songs',
                        'uri' => 'spotify:user:liked-songs',
                        'type' => 'playlist',
                        'images' => [[
                            'url' => 'https://t.scdn.co/images/3099b3803ad9496896c43f22fe9be8c4.png',
                            'height' => 300,
                            'width' => 300,
                        ]],
                        'owner' => [
                            'display_name' => 'You',
                        ],
                        'tracks' => [
                            'total' => count($savedTracks),
                        ],
                    ]);
                }
            }

            return ['playlists' => $playlists];
        });
    }

    public function shufflePlayPlaylist(string $uri): array
    {
        if ($uri && !$this->playback->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        $shuffleResult = $this->playback->setShuffle(true);

        if (isset($shuffleResult['error'])) {
            return $shuffleResult;
        }

        $result = $this->playback->play($uri);
        $this->clearPlaylistCache();

        return $result;
    }

    public function clearPlaylistCache(): void
    {
        Cache::forget('spotify_playlists_' . md5('20_1'));
        Cache::forget('spotify_playlists_' . md5('20_0'));
    }

    public function search(string $query, string $type = 'track', int $limit = 20): array
    {
        return $this->api->request('GET', '/search', [
            'query' => [
                'q' => $query,
                'type' => $type,
                'limit' => $limit,
            ],
        ]);
    }

    public function checkSavedTracks(array $ids): array
    {
        return $this->api->request('GET', '/me/tracks/contains', [
            'query' => ['ids' => implode(',', $ids)],
        ]);
    }

    public function saveTracks(array $ids): array
    {
        $result = $this->api->request('PUT', '/me/tracks', [
            'json' => ['ids' => $ids],
        ]);

        $this->dispatchTrackLiked($ids, true, $result);

        return $result;
    }

    public function removeTracks(array $ids): array
    {
        $result = $this->api->request('DELETE', '/me/tracks', [
            'json' => ['ids' => $ids],
        ]);

        $this->dispatchTrackLiked($ids, false, $result);

        return $result;
    }

    protected function dispatchTrackLiked(array $ids, bool $saved, array $result): void
    {
        if (isset($result['error'])) {
            return;
        }

        foreach ($ids as $id) {
            event(new TrackLiked($id, $saved));
        }
    }
}
