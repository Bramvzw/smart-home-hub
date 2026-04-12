<?php

namespace Modules\Spotify\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PlaybackChanged
{
    use Dispatchable;

    public function __construct(public array $playbackState) {}
}
