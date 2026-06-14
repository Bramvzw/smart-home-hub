<?php

namespace Modules\Spotify\Services;

use GuzzleHttp\ClientInterface;

class SpotifyService
{
    protected SpotifyTokenService $tokens;

    protected SpotifyPlaybackService $playback;

    protected SpotifyLibraryService $library;

    public function __construct(?ClientInterface $client = null)
    {
        $this->tokens = new SpotifyTokenService($client);
        $api = new SpotifyApiClient($client, $this->tokens);
        $this->playback = new SpotifyPlaybackService($api);
        $this->library = new SpotifyLibraryService($api, $this->playback);
    }

    public function getAuthorizationUrl(): string
    {
        return $this->tokens->getAuthorizationUrl();
    }

    public function getAccessToken(string $code): array
    {
        return $this->tokens->getAccessToken($code);
    }

    public function refreshAccessToken(): array
    {
        return $this->tokens->refreshAccessToken();
    }

    public function hasStoredAuthorization(): bool
    {
        return $this->tokens->hasStoredAuthorization();
    }

    public function ensureAccessToken(): array
    {
        return $this->tokens->ensureAccessToken();
    }

    public function getProfile(): array
    {
        return $this->playback->getProfile();
    }

    public function getCurrentPlayback(): array
    {
        return $this->playback->getCurrentPlayback();
    }

    public function play(?string $uri = null): array
    {
        return $this->playback->play($uri);
    }

    public function pause(): array
    {
        return $this->playback->pause();
    }

    public function next(): array
    {
        return $this->playback->next();
    }

    public function previous(): array
    {
        return $this->playback->previous();
    }

    public function setVolume(int $volume): array
    {
        return $this->playback->setVolume($volume);
    }

    public function seekToPosition(int $positionMs): array
    {
        return $this->playback->seekToPosition($positionMs);
    }

    public function getQueue(): array
    {
        return $this->playback->getQueue();
    }

    public function getNextTrack(): array
    {
        return $this->playback->getNextTrack();
    }

    public function setShuffle(bool $state): array
    {
        return $this->playback->setShuffle($state);
    }

    public function setRepeatMode(string $state): array
    {
        return $this->playback->setRepeatMode($state);
    }

    public function addToQueue(string $uri): array
    {
        return $this->playback->addToQueue($uri);
    }

    public function getRecentlyPlayed(int $limit = 20): array
    {
        return $this->playback->getRecentlyPlayed($limit);
    }

    public function getAvailableDevices(): array
    {
        return $this->playback->getAvailableDevices();
    }

    public function transferPlayback(string $deviceId, bool $play = true): array
    {
        return $this->playback->transferPlayback($deviceId, $play);
    }

    public function getSavedTracks(int $limit = 20): array
    {
        return $this->library->getSavedTracks($limit);
    }

    public function getUserPlaylists(int $limit = 20, bool $includeLikedSongs = true): array
    {
        return $this->library->getUserPlaylists($limit, $includeLikedSongs);
    }

    public function shufflePlayPlaylist(string $uri): array
    {
        return $this->library->shufflePlayPlaylist($uri);
    }

    public function clearPlaylistCache(): void
    {
        $this->library->clearPlaylistCache();
    }

    public function search(string $query, string $type = 'track', int $limit = 20): array
    {
        return $this->library->search($query, $type, $limit);
    }

    public function checkSavedTracks(array $ids): array
    {
        return $this->library->checkSavedTracks($ids);
    }

    public function saveTracks(array $ids): array
    {
        return $this->library->saveTracks($ids);
    }

    public function removeTracks(array $ids): array
    {
        return $this->library->removeTracks($ids);
    }
}
