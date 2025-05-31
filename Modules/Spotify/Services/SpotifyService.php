<?php

namespace Modules\Spotify\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpotifyService
{
    protected $client;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $apiUrl = 'https://api.spotify.com/v1';
    protected $authUrl = 'https://accounts.spotify.com/api/token';

    public function __construct()
    {
        $this->client = new Client();
        $this->clientId = config('services.spotify.client_id');
        $this->clientSecret = config('services.spotify.client_secret');
        $this->redirectUri = config('services.spotify.redirect_uri');
    }

    /**
     * Get the authorization URL for Spotify
     *
     * @return string
     */
    public function getAuthorizationUrl()
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

        $query = http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => bin2hex(random_bytes(16)),
        ]);

        return 'https://accounts.spotify.com/authorize?' . $query;
    }

    /**
     * Get access token from authorization code
     *
     * @param string $code
     * @return array
     */
    public function getAccessToken($code)
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
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Refresh access token
     *
     * @return array
     */
    public function refreshAccessToken()
    {
        $refreshToken = Cache::get('spotify_refresh_token');

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
            return ['error' => $e->getMessage()];
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
            Cache::put('spotify_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        }

        if (isset($data['refresh_token'])) {
            Cache::put('spotify_refresh_token', $data['refresh_token'], now()->addDays(30));
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
    public function getCurrentPlayback()
    {
        return $this->makeRequest('GET', '/me/player');
    }

    /**
     * Start or resume playback
     *
     * @param string|null $uri URI of the track, album, or playlist to play
     * @return array
     */
    public function play($uri = null)
    {
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

        return $this->makeRequest('PUT', '/me/player/play', $options);
    }

    /**
     * Pause playback
     *
     * @return array
     */
    public function pause()
    {
        return $this->makeRequest('PUT', '/me/player/pause');
    }

    /**
     * Skip to next track
     *
     * @return array
     */
    public function next()
    {
        return $this->makeRequest('POST', '/me/player/next');
    }

    /**
     * Skip to previous track
     *
     * @return array
     */
    public function previous()
    {
        return $this->makeRequest('POST', '/me/player/previous');
    }

    /**
     * Set volume
     *
     * @param int $volumePercent
     * @return array
     */
    public function setVolume($volumePercent)
    {
        return $this->makeRequest('PUT', '/me/player/volume', [
            'query' => ['volume_percent' => $volumePercent]
        ]);
    }

    /**
     * Seek to position in currently playing track
     *
     * @param int $positionMs
     * @return array
     */
    public function seekToPosition($positionMs)
    {
        return $this->makeRequest('PUT', '/me/player/seek', [
            'query' => ['position_ms' => $positionMs]
        ]);
    }

    /**
     * Get the next track in the queue
     *
     * @return array
     */
    public function getNextTrack()
    {
        $queue = $this->makeRequest('GET', '/me/player/queue');

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
    public function getSavedTracks($limit = 50)
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
    public function getUserPlaylists($limit = 20, $includeLikedSongs = true)
    {
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
    }

    /**
     * Get the user's recently played playlists/albums
     *
     * @param int $limit
     * @return array
     */

    /**
     * Start playback with shuffle mode enabled for a playlist
     *
     * @param string $playlistUri
     * @return array
     */
    public function shufflePlayPlaylist($playlistUri)
    {
        // First enable shuffle mode
        $shuffleResult = $this->makeRequest('PUT', '/me/player/shuffle', [
            'query' => ['state' => 'true']
        ]);

        if (isset($shuffleResult['error'])) {
            return $shuffleResult;
        }

        // Then start playing the playlist
        return $this->play($playlistUri);
    }

    /**
     * Check if tracks are saved in the user's library
     *
     * @param array $ids
     * @return array
     */
    public function checkSavedTracks($ids)
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
    public function saveTracks($ids)
    {
        return $this->makeRequest('PUT', '/me/tracks', [
            'json' => ['ids' => $ids]
        ]);
    }

    /**
     * Remove tracks from the user's library
     *
     * @param array $ids
     * @return array
     */
    public function removeTracks($ids)
    {
        return $this->makeRequest('DELETE', '/me/tracks', [
            'json' => ['ids' => $ids]
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
    protected function makeRequest($method, $endpoint, $options = [])
    {
        $accessToken = Cache::get('spotify_access_token');

        if (!$accessToken) {
            $refreshResult = $this->refreshAccessToken();
            if (isset($refreshResult['error'])) {
                return $refreshResult;
            }
            $accessToken = Cache::get('spotify_access_token');
        }

        try {
            $options['headers'] = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ];

            $response = $this->client->request($method, $this->apiUrl . $endpoint, $options);

            if ($response->getStatusCode() === 204) {
                return ['success' => true];
            }

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();

            // If token expired, refresh and try again
            if ($statusCode === 401) {
                $refreshResult = $this->refreshAccessToken();
                if (isset($refreshResult['error'])) {
                    return $refreshResult;
                }

                // Try the request again with the new token
                return $this->makeRequest($method, $endpoint, $options);
            }

            // Handle specific case for volume control not supported
            if ($statusCode === 403 && strpos($e->getMessage(), 'Cannot control device volume') !== false) {
                Log::warning('Spotify API warning: Device does not support volume control');
                return ['error' => 'This device does not support volume control', 'code' => 'volume_control_not_supported'];
            }

            Log::error('Spotify API error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
