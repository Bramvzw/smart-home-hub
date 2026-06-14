<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\View\ViewModels\UpcomingTrackViewModel;

class SpotifyPlayer extends Component
{
    public bool $isPlaying;
    public bool $hasCurrentTrack;

    /**
     * @param  list<\Modules\Spotify\View\ViewModels\PlaylistView>  $playlists
     */
    public function __construct(
        public ?array $playbackState,
        public bool $isConnected,
        public string $authUrl,
        public ?UpcomingTrackViewModel $upcomingTrack = null,
        public array $playlists = [],
    ) {
        $this->playbackState ??= [];
        $this->isPlaying = $this->playbackState['is_playing'] ?? false;
        $this->hasCurrentTrack = isset($this->playbackState['item']) && is_array($this->playbackState['item']);
    }

    public function hasPlaylists(): bool
    {
        return $this->isConnected;
    }

    public function render()
    {
        return view('spotify::spotify-player');
    }
}
