<?php

namespace Modules\Spotify\View\ViewModels;

use Illuminate\Support\Facades\Log;
use Modules\Spotify\Services\SpotifyService;

class SpotifyPlayerViewModel
{
    public function __construct(
        private readonly SpotifyService $service,
    ) {}

    /**
     * Read model for the Spotify player page. All external calls happen here so
     * the Blade components stay side-effect free and only render passed-in data.
     */
    public function page(): array
    {
        $isConnected = $this->service->hasStoredAuthorization();
        $playbackState = null;
        $upcomingTrack = null;
        $playlists = [];

        if ($isConnected) {
            $token = $this->service->ensureAccessToken();

            if (isset($token['error'])) {
                Log::warning('Spotify authorization could not be refreshed before rendering player', [
                    'error' => $token['error'],
                ]);
                $isConnected = false;
            } else {
                $playbackState = $this->service->getCurrentPlayback();
                $playlists = $this->resolvePlaylists();

                if ($this->hasCurrentTrack($playbackState)) {
                    $nextTrack = $this->service->getNextTrack();
                    $upcomingTrack = new UpcomingTrackViewModel($nextTrack['next_track'] ?? null);
                }
            }
        }

        return [
            'isConnected' => $isConnected,
            'playbackState' => $playbackState,
            'authUrl' => $isConnected ? '' : $this->service->getAuthorizationUrl(),
            'upcomingTrack' => $upcomingTrack,
            'playlists' => $playlists,
        ];
    }

    private function hasCurrentTrack(?array $playbackState): bool
    {
        return isset($playbackState['item']) && is_array($playbackState['item']);
    }

    /**
     * @return list<PlaylistView>
     */
    private function resolvePlaylists(): array
    {
        $response = $this->service->getUserPlaylists();

        return array_map(
            static fn (array $playlist): PlaylistView => new PlaylistView($playlist),
            $response['playlists'] ?? [],
        );
    }
}
