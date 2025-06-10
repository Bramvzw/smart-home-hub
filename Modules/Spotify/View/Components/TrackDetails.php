<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;
use Modules\Spotify\View\ViewModels\TrackDetailsView;

class TrackDetails extends Component
{
    public TrackDetailsView $trackDetails;

    public function __construct($playbackState = [])
    {
        $this->trackDetails = new TrackDetailsView($playbackState);
    }

    public function trackImage()
    {
        return $this->playbackState['item']['album']['images'][0]['url'] ?? '/images/no-track.webp';
    }

    public function render()
    {
        return view('spotify::components.track-details');
    }
}
