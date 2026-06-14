<?php

namespace Modules\Spotify\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class SpotifyTokenService
{
    protected ClientInterface $client;

    protected string $clientId;

    protected string $clientSecret;

    protected string $redirectUri;

    protected string $authUrl = 'https://accounts.spotify.com/api/token';

    public function __construct(?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
        $this->clientId = (string) config('services.spotify.client_id');
        $this->clientSecret = (string) config('services.spotify.client_secret');
        $this->redirectUri = (string) config('services.spotify.redirect_uri');
    }

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

    public function hasAccessToken(): bool
    {
        return Cache::has('spotify_access_token');
    }

    public function hasRefreshToken(): bool
    {
        return Cache::has('spotify_refresh_token');
    }

    public function hasStoredAuthorization(): bool
    {
        return $this->hasAccessToken() || $this->hasRefreshToken();
    }

    public function ensureAccessToken(): array
    {
        if ($this->hasAccessToken()) {
            return ['success' => true];
        }

        return $this->refreshAccessToken();
    }

    public function getAccessToken(string $code): array
    {
        try {
            $response = $this->client->post($this->authUrl, [
                'headers' => $this->authHeaders(),
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $this->redirectUri,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $this->storeTokens($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Spotify token error: ' . $e->getMessage());

            return ['error' => 'Failed to obtain Spotify access token'];
        }
    }

    public function refreshAccessToken(): array
    {
        $refreshToken = Cache::get('spotify_refresh_token');

        if (!$refreshToken) {
            return ['error' => 'No refresh token available'];
        }

        try {
            $response = $this->client->post($this->authUrl, [
                'headers' => $this->authHeaders(),
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $this->clientId,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $this->storeTokens($data);

            return $data;
        } catch (GuzzleException $e) {
            Log::error('Spotify token refresh error: ' . $e->getMessage());

            return ['error' => 'Failed to refresh Spotify access token'];
        }
    }

    protected function storeTokens(array $data): void
    {
        if (isset($data['access_token'])) {
            Cache::put('spotify_access_token', $data['access_token'], now()->addSeconds($data['expires_in'] - 60));
        }

        if (isset($data['refresh_token'])) {
            Cache::forever('spotify_refresh_token', $data['refresh_token']);
        }
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }
}
