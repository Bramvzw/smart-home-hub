<?php

namespace Modules\Spotify\Services;

use Modules\Spotify\Events\PlaybackChanged;

class SpotifyPlaybackService
{
    public function __construct(protected SpotifyApiClient $api) {}

    public function getProfile(): array
    {
        return $this->api->request('GET', '/me');
    }

    public function getCurrentPlayback(): array
    {
        return $this->api->request('GET', '/me/player');
    }

    public function play(?string $uri = null): array
    {
        if ($uri && !$this->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        $options = [];

        if ($uri) {
            $options['json'] = str_starts_with($uri, 'spotify:track:')
                ? ['uris' => [$uri]]
                : ['context_uri' => $uri];
        }

        $result = $this->api->request('PUT', '/me/player/play', $options);
        $this->dispatchPlaybackChanged($result);

        return $result;
    }

    public function pause(): array
    {
        $result = $this->api->request('PUT', '/me/player/pause');
        $this->dispatchPlaybackChanged($result);

        return $result;
    }

    public function next(): array
    {
        $result = $this->api->request('POST', '/me/player/next');
        $this->dispatchPlaybackChanged($result);

        return $result;
    }

    public function previous(): array
    {
        $result = $this->api->request('POST', '/me/player/previous');
        $this->dispatchPlaybackChanged($result);

        return $result;
    }

    public function setVolume(int $volume): array
    {
        return $this->api->request('PUT', '/me/player/volume', [
            'query' => ['volume_percent' => $volume],
        ]);
    }

    public function seekToPosition(int $positionMs): array
    {
        return $this->api->request('PUT', '/me/player/seek', [
            'query' => ['position_ms' => $positionMs],
        ]);
    }

    public function getQueue(): array
    {
        return $this->api->request('GET', '/me/player/queue');
    }

    public function getNextTrack(): array
    {
        $queue = $this->getQueue();

        if (isset($queue['queue']) && !empty($queue['queue'])) {
            return ['next_track' => $queue['queue'][0]];
        }

        return ['next_track' => null];
    }

    public function setShuffle(bool $state): array
    {
        return $this->api->request('PUT', '/me/player/shuffle', [
            'query' => ['state' => $state ? 'true' : 'false'],
        ]);
    }

    public function setRepeatMode(string $state): array
    {
        return $this->api->request('PUT', '/me/player/repeat', [
            'query' => ['state' => $state],
        ]);
    }

    public function addToQueue(string $uri): array
    {
        if ($uri && !$this->validateSpotifyUri($uri)) {
            return ['error' => 'Invalid Spotify URI format'];
        }

        return $this->api->request('POST', '/me/player/queue', [
            'query' => ['uri' => $uri],
        ]);
    }

    public function getRecentlyPlayed(int $limit = 20): array
    {
        return $this->api->request('GET', '/me/player/recently-played', [
            'query' => ['limit' => $limit],
        ]);
    }

    public function getAvailableDevices(): array
    {
        return $this->api->request('GET', '/me/player/devices');
    }

    public function transferPlayback(string $deviceId, bool $play = true): array
    {
        return $this->api->request('PUT', '/me/player', [
            'json' => ['device_ids' => [$deviceId], 'play' => $play],
        ]);
    }

    public function validateSpotifyUri(string $uri): bool
    {
        return (bool) preg_match('/^spotify:(track|album|playlist|artist|show|episode):[a-zA-Z0-9]{22}$/', $uri);
    }

    protected function dispatchPlaybackChanged(array $result): void
    {
        if (!isset($result['error'])) {
            event(new PlaybackChanged([]));
        }
    }
}
