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
     * @return array
     */
    public function play()
    {
        return $this->makeRequest('PUT', '/me/player/play');
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
