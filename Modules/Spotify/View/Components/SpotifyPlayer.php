<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;

class SpotifyPlayer extends Component
{
    public ?array $playbackState;
    public bool $isConnected;
    public string $authUrl;
    public bool $isPlaying;

    public function __construct($playbackState, $isConnected, $authUrl)
    {
        $this->playbackState = $playbackState;
        $this->isConnected = $isConnected;
        $this->authUrl = $authUrl;
        $this->isPlaying = $this->playbackState['is_playing'] ?? false;
    }

        public function hasPlaylists(): bool
    {
        return !empty($this->playbackState['playlists']);
    }



    public function render()
    {
        return view('spotify::spotify-player');
    }
}
