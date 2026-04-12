<?php

namespace Modules\Spotify\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Modules\Spotify\Events\PlaybackChanged;
use Modules\Spotify\Events\TrackLiked;

class SpotifyService
{
    protected ClientInterface $client;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $apiUrl = 'https://api.spotify.com/v1';
    protected $authUrl = 'https://accounts.spotify.com/api/token';

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
        $this->clientId = config('services.spotify.client_id');
        $this->clientSecret = config('services.spotify.client_secret');
        $this->redirectUri = config('services.spotify.redirect_uri');
    }

    /**
     * Get the authorization URL for Spotify
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $scopes = [
            'user-read-private',
            'user-read-email',
            'user-modify-playback-state',
            'user-read-playback-state',
            'user-read-currently-playing',
            'user-library-read',
            'user-library-modify',
            'user-read-recently-played',
            'playlist-read-private',
            'playlist-read-collaborative',
        ];

        $state = bin2hex(random_bytes(16));
        Session::put('spotify_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
        ]);

        return 'https://accounts.spotify.com/authorize?' . $query;
    }

    /**
     * Get access token from authorization code
     *
     * @param string $code
     * @return array
     */
    public function getAccessToken(string $code): array
    {
        try {
            $response = $this->client->post('https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri, // must match exactly
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // Store tokens in cache
            $this->storeTokens($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Spotify token error: ' . $e->getMessage());
            return ['error' => 'Failed to obtain Spotify access token'];
        }
    }

    /**
     * Refresh access token
     *
     * @return array
     */
    public function refreshAccessToken(): array
    {
        $refreshToken = Cache::store('database')->get('spotify_refresh_token');

        if (!$refreshToken) {
            return ['error' => 'No refresh token available'];
        }

        try {
            $response = $this->client->post($this->authUrl, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            // Update access token in cache
            $this->storeTokens($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Spotify token refresh error: ' . $e->getMessage());
            return ['error' => 'Failed to refresh Spotify access token'];
        }
    }

    /**
     * Store tokens in cache
     *
     * @param array $data
     * @return void
     */
    protected function storeTokens($data)
    {
        if (isset($data['access_token'])) {
            Cache::store('database')->put('spotify_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        }

        if (isset($data['refresh_token'])) {
            Cache::store('database')->put('spotify_refresh_token', $data['refresh_token'], now()->addDays(30));
        }
    }

    /**
     * Get the current user's profile
     *
     * @return array
     */
    public function getProfile()
    {
        return $this->makeRequest('GET', '/me');
    }

    /**
     * Get the current playback state
     *
     * @return array
     */
    public function getCurrentPlayback(): array
    {
        return $this->makeRequest('GET', '/me/player');
    }

    /**
     * Start or resume playback
     *
     * @param string|null $uri URI of the track, album, or playlist to play
     * @return array
     */
    private function validateSpotifyUri(string $uri): bool
    {
        return (bool) preg_match('/^spotify:(track|album|playlist|artist|show|episode):[a-zA-Z0-9]{22}$/', $uri);
    }

    public function play(?string $uri = null): array
    {
        if ($uri && !$this->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        $options = [];

        if ($uri) {
            if (strpos($uri, 'spotify:track:') === 0) {
                // If it's a track URI, use uris array with a single item
                $options['json'] = ['uris' => [$uri]];
            } else {
                // If it's an album or playlist URI, use context_uri
                $options['json'] = ['context_uri' => $uri];
            }
        }

        $result = $this->makeRequest('PUT', '/me/player/play', $options);

        if (!isset($result['error'])) {
            Cache::forget('spotify_recently_played');
            event(new PlaybackChanged([]));
        }

        return $result;
    }

    /**
     * Pause playback
     *
     * @return array
     */
    public function pause(): array
    {
        $result = $this->makeRequest('PUT', '/me/player/pause');

        if (!isset($result['error'])) {
            event(new PlaybackChanged([]));
        }

        return $result;
    }

    /**
     * Skip to next track
     *
     * @return array
     */
    public function next(): array
    {
        $result = $this->makeRequest('POST', '/me/player/next');

        if (!isset($result['error'])) {
            Cache::forget('spotify_recently_played');
            event(new PlaybackChanged([]));
        }

        return $result;
    }

    /**
     * Skip to previous track
     *
     * @return array
     */
    public function previous(): array
    {
        $result = $this->makeRequest('POST', '/me/player/previous');

        if (!isset($result['error'])) {
            Cache::forget('spotify_recently_played');
            event(new PlaybackChanged([]));
        }

        return $result;
    }

    /**
     * Set volume
     *
     * @param int $volumePercent
     * @return array
     */
    public function setVolume(int $volume): array
    {
        return $this->makeRequest('PUT', '/me/player/volume', [
            'query' => ['volume_percent' => $volume]
        ]);
    }

    /**
     * Seek to position in currently playing track
     *
     * @param int $positionMs
     * @return array
     */
    public function seekToPosition(int $positionMs): array
    {
        return $this->makeRequest('PUT', '/me/player/seek', [
            'query' => ['position_ms' => $positionMs]
        ]);
    }

    /**
     * Get the full playback queue
     *
     * @return array
     */
    public function getQueue(): array
    {
        return $this->makeRequest('GET', '/me/player/queue');
    }

    /**
     * Get the next track in the queue
     *
     * @return array
     */
    public function getNextTrack(): array
    {
        $queue = $this->getQueue();

        if (isset($queue['queue']) && !empty($queue['queue'])) {
            return ['next_track' => $queue['queue'][0]];
        }

        return ['next_track' => null];
    }

    /**
     * Get the user's saved tracks (liked songs)
     *
     * @param int $limit
     * @return array
     */
    public function getSavedTracks(int $limit = 20): array
    {
        $response = $this->makeRequest('GET', '/me/tracks', [
            'query' => [
                'limit' => $limit
            ]
        ]);

        if (!isset($response['items']) || empty($response['items'])) {
            return [];
        }

        return $response['items'];
    }

    /**
     * Get the user's library playlists
     *
     * @param int $limit
     * @param bool $includeLikedSongs
     * @return array
     */
    public function getUserPlaylists(int $limit = 20, bool $includeLikedSongs = true): array
    {
        $cacheKey = 'spotify_playlists_' . md5($limit . '_' . ($includeLikedSongs ? '1' : '0'));
        return Cache::remember($cacheKey, 300, function () use ($limit, $includeLikedSongs) {
            // Get the user's playlists directly from the API
            $response = $this->makeRequest('GET', '/me/playlists', [
                'query' => [
                    'limit' => $limit
                ]
            ]);

            $playlists = [];

            if (isset($response['items']) && !empty($response['items'])) {
                $playlists = $response['items'];
            }

            // Include liked songs as a special playlist if requested
            if ($includeLikedSongs) {
                // Get a sample of liked songs to use as a preview
                $savedTracks = $this->getSavedTracks(5);

                if (!empty($savedTracks)) {
                    // Create a special playlist for liked songs
                    $likedSongsPlaylist = [
                        'id' => 'liked-songs',
                        'name' => 'Liked Songs',
                        'uri' => 'spotify:user:liked-songs',
                        'type' => 'playlist',
                        'images' => [
                            [
                                'url' => 'https://t.scdn.co/images/3099b3803ad9496896c43f22fe9be8c4.png',
                                'height' => 300,
                                'width' => 300
                            ]
                        ],
                        'owner' => [
                            'display_name' => 'You'
                        ],
                        'tracks' => [
                            'total' => count($savedTracks)
                        ]
                    ];

                    // Add the liked songs playlist to the beginning of the array
                    array_unshift($playlists, $likedSongsPlaylist);
                }
            }

            return ['playlists' => $playlists];
        });
    }

    /**
     * Start playback with shuffle mode enabled for a playlist
     *
     * @param string $playlistUri
     * @return array
     */
    public function shufflePlayPlaylist(string $uri): array
    {
        if ($uri && !$this->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        // First enable shuffle mode
        $shuffleResult = $this->makeRequest('PUT', '/me/player/shuffle', [
            'query' => ['state' => 'true']
        ]);

        if (isset($shuffleResult['error'])) {
            return $shuffleResult;
        }

        // Then start playing the playlist
        $result = $this->play($uri);
        $this->clearPlaylistCache();
        return $result;
    }

    /**
     * Clear the cached playlist data
     *
     * @return void
     */
    public function clearPlaylistCache(): void
    {
        Cache::forget('spotify_playlists_' . md5('20_1'));
        Cache::forget('spotify_playlists_' . md5('20_0'));
    }

    /**
     * Toggle shuffle mode
     *
     * @param bool $state
     * @return array
     */
    public function setShuffle(bool $state): array
    {
        return $this->makeRequest('PUT', '/me/player/shuffle', [
            'query' => ['state' => $state ? 'true' : 'false']
        ]);
    }

    /**
     * Set repeat mode
     *
     * @param string $state off, context, track
     * @return array
     */
    public function setRepeatMode(string $state): array
    {
        return $this->makeRequest('PUT', '/me/player/repeat', [
            'query' => ['state' => $state]
        ]);
    }

    /**
     * Add a track to the playback queue
     *
     * @param string $uri
     * @return array
     */
    public function addToQueue(string $uri): array
    {
        if ($uri && !$this->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        return $this->makeRequest('POST', '/me/player/queue', [
            'query' => ['uri' => $uri]
        ]);
    }

    /**
     * Get recently played tracks
     *
     * @param int $limit
     * @return array
     */
    public function getRecentlyPlayed(int $limit = 20): array
    {
        return Cache::remember('spotify_recently_played', 120, function () use ($limit) {
            return $this->makeRequest('GET', '/me/player/recently-played', [
                'query' => ['limit' => $limit]
            ]);
        });
    }

    /**
     * Search for tracks, artists, or albums
     *
     * @param string $query
     * @param string $type comma-separated: track,artist,album
     * @param int $limit
     * @return array
     */
    public function search(string $query, string $type = 'track', int $limit = 20): array
    {
        return $this->makeRequest('GET', '/search', [
            'query' => [
                'q' => $query,
                'type' => $type,
                'limit' => $limit,
            ]
        ]);
    }

    /**
     * Check if tracks are saved in the user's library
     *
     * @param array $ids
     * @return array
     */
    public function checkSavedTracks(array $ids): array
    {
        return $this->makeRequest('GET', '/me/tracks/contains', [
            'query' => ['ids' => implode(',', $ids)]
        ]);
    }

    /**
     * Save tracks to the user's library
     *
     * @param array $ids
     * @return array
     */
    public function saveTracks(array $ids): array
    {
        $result = $this->makeRequest('PUT', '/me/tracks', [
            'json' => ['ids' => $ids]
        ]);

        if (!isset($result['error'])) {
            foreach ($ids as $id) {
                event(new TrackLiked($id, true));
            }
        }

        return $result;
    }

    /**
     * Remove tracks from the user's library
     *
     * @param array $ids
     * @return array
     */
    public function removeTracks(array $ids): array
    {
        $result = $this->makeRequest('DELETE', '/me/tracks', [
            'json' => ['ids' => $ids]
        ]);

        if (!isset($result['error'])) {
            foreach ($ids as $id) {
                event(new TrackLiked($id, false));
            }
        }

        return $result;
    }

    /**
     * Get available devices
     *
     * @return array
     */
    public function getAvailableDevices(): array
    {
        return $this->makeRequest('GET', '/me/player/devices');
    }

    /**
     * Transfer playback to a device
     *
     * @param string $deviceId
     * @param bool $play
     * @return array
     */
    public function transferPlayback(string $deviceId, bool $play = true): array
    {
        return $this->makeRequest('PUT', '/me/player', [
            'json' => ['device_ids' => [$deviceId], 'play' => $play]
        ]);
    }

    /**
     * Make a request to the Spotify API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return array
     */
    protected function makeRequest($method, $endpoint, $options = [], bool $retried = false)
    {
        $accessToken = Cache::store('database')->get('spotify_access_token');

        if (!$accessToken) {
            $refreshResult = $this->refreshAccessToken();
            if (isset($refreshResult['error'])) {
                return $refreshResult;
            }
            $accessToken = Cache::store('database')->get('spotify_access_token');
        }

        try {
            $options['headers'] = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ];

            $response = $this->client->request($method, $this->apiUrl . $endpoint, $options);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode === 204 || $body === '') {
                return ['success' => true];
            }

            $decoded = json_decode($body, true);
            return is_array($decoded) ? $decoded : ['error' => 'Invalid response from Spotify API'];
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();

            // If token expired and not already retried, refresh and try once more
            if ($statusCode === 401 && !$retried) {
                $refreshResult = $this->refreshAccessToken();
                if (isset($refreshResult['error'])) {
                    return $refreshResult;
                }

                return $this->makeRequest($method, $endpoint, $options, true);
            }

            // Handle specific case for volume control not supported
            if ($statusCode === 403 && strpos($e->getMessage(), 'Cannot control device volume') !== false) {
                Log::warning('Spotify API warning: Device does not support volume control');
                return ['error' => 'This device does not support volume control', 'code' => 'volume_control_not_supported'];
            }

            Log::error('Spotify API error: ' . $e->getMessage());
            return ['error' => 'Spotify API request failed'];
        }
    }
}
