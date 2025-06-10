<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\Services\SpotifyService;
use Modules\Spotify\View\ViewModels\UpcomingTrackView;

class UpcomingTrack extends Component
{
    public ?UpcomingTrackView $upcomingTrack;

    public function __construct(SpotifyService $spotifyService)
    {
        $nextTrackData = $spotifyService->getNextTrack();
        $this->upcomingTrack = new UpcomingTrackView($nextTrackData['next_track'] ?? null);
    }

    public function render()
    {
        return view('spotify::components.upcoming-track');
    }
}
