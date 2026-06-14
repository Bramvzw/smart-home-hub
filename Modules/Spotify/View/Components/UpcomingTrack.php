<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\View\ViewModels\UpcomingTrackViewModel;

class UpcomingTrack extends Component
{
    public function __construct(
        public ?UpcomingTrackViewModel $upcomingTrack = null,
    ) {}

    public function render()
    {
        return view('spotify::components.upcoming-track');
    }
}
