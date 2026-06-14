<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\View\ViewModels\UpcomingTrackView;

class TrackDetails extends Component
{
    public function __construct(
        public array $playbackState = [],
        public ?UpcomingTrackView $upcomingTrack = null,
    ) {}

    public function render()
    {
        return view('spotify::components.track-details');
    }
}
