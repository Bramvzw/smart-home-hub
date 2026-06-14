<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\Services\SpotifyService;
use Modules\Spotify\View\ViewModels\UpcomingTrackView;

class SpotifyPlayer extends Component
{
    public bool $isPlaying;
    public bool $hasCurrentTrack;
    public ?UpcomingTrackView $upcomingTrack;

    public function __construct(
        public ?array $playbackState,
        public bool $isConnected,
        public string $authUrl,
        SpotifyService $spotifyService,
    ) {
        $this->playbackState ??= [];
        $this->isPlaying = $this->playbackState['is_playing'] ?? false;
        $this->hasCurrentTrack = isset($this->playbackState['item']) && is_array($this->playbackState['item']);

        if ($this->hasCurrentTrack) {
            $nextTrackData = $spotifyService->getNextTrack();
            $this->upcomingTrack = new UpcomingTrackView($nextTrackData['next_track'] ?? null);
        } else {
            $this->upcomingTrack = null;
        }
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
