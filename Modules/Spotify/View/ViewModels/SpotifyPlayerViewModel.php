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
            }
        }

        return [
            'isConnected' => $isConnected,
            'playbackState' => $playbackState,
            'authUrl' => $isConnected ? '' : $this->service->getAuthorizationUrl(),
            // The next track is fetched by the player JS after first paint: it is
            // another Spotify round trip (~0.5 s) the kiosk shouldn't wait on.
            'upcomingTrack' => null,
            'playlists' => $playlists,
        ];
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
