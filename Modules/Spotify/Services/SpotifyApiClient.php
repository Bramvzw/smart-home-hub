<?php

namespace Modules\Spotify\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SpotifyApiClient
{
    protected ClientInterface $client;

    protected string $apiUrl = 'https://api.spotify.com/v1';

    public function __construct(
        ?ClientInterface $client = null,
        protected ?SpotifyTokenService $tokens = null,
    ) {
        $this->client = $client ?? new Client();
        $this->tokens ??= new SpotifyTokenService($this->client);
    }

    public function request(string $method, string $endpoint, array $options = [], bool $retried = false): array
    {
        $accessToken = Cache::get('spotify_access_token');

        if (!$accessToken) {
            $refreshResult = $this->tokens->refreshAccessToken();
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
            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();

            if ($statusCode === 204 || $body === '') {
                return ['success' => true];
            }

            $decoded = json_decode($body, true);

            if (is_array($decoded)) {
                return $decoded;
            }

            if ($statusCode >= 200 && $statusCode < 300) {
                return ['success' => true];
            }

            return ['error' => 'Invalid response from Spotify API'];
        } catch (GuzzleException $e) {
            $statusCode = $e->getCode();

            if ($statusCode === 401 && !$retried) {
                $refreshResult = $this->tokens->refreshAccessToken();
                if (isset($refreshResult['error'])) {
                    return $refreshResult;
                }

                return $this->request($method, $endpoint, $options, true);
            }

            if ($statusCode === 403 && str_contains($e->getMessage(), 'Cannot control device volume')) {
                Log::warning('Spotify API warning: Device does not support volume control');

                return ['error' => 'This device does not support volume control', 'code' => 'volume_control_not_supported'];
            }

            Log::error('Spotify API error: ' . $e->getMessage());

            return ['error' => 'Spotify API request failed'];
        }
    }
}
