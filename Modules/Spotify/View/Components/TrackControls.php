<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;

class TrackControls extends Component
{
    public function __construct(
        public array $playbackState = [],
    ) {}

    public function render()
    {
        return view('spotify::components.track-controls');
    }
}
