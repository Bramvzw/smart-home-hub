<?php

namespace Modules\Spotify\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TrackLiked
{
    use Dispatchable;

    public function __construct(public string $trackId, public bool $saved) {}
}
