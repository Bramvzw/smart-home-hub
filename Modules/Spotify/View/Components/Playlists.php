<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\Services\SpotifyService;
use Modules\Spotify\View\ViewModels\PlaylistView;

class Playlists extends Component
{
    protected SpotifyService $spotifyService;
    public array $playlists = [];

    public function __construct(SpotifyService $spotifyService)
    {
        $this->spotifyService = $spotifyService;
        $this->playlists = $this->fetchPlaylists();
    }

    /**
     * Fetch playlists and wrap them as PlaylistView objects.
     */
    protected function fetchPlaylists(): array
    {
        $response = $this->spotifyService->getUserPlaylists();
        $playlistsData = $response['playlists'] ?? [];

        $playlists = [];
        foreach ($playlistsData as $playlistData) {
            $playlists[] = new PlaylistView($playlistData);
        }

        return $playlists;
    }

    public function render()
    {
        return view('spotify::components.playlists', [
            'playlists' => $this->playlists,
        ]);
    }
}
