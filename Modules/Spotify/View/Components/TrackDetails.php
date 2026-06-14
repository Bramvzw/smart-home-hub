<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\View\ViewModels\UpcomingTrackViewModel;

class TrackDetails extends Component
{
    public function __construct(
        public array $playbackState = [],
        public ?UpcomingTrackViewModel $upcomingTrack = null,
    ) {}

    public function render()
    {
        return view('spotify::components.track-details');
    }
}
