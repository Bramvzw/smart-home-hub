<?php

namespace Modules\Spotify\View\ViewModels;

class UpcomingTrackView
{
    public string $trackImage;
    public string $trackName;
    public string $artistNames;

    public function __construct(?array $track = null)
    {
        $this->trackImage = $track['album']['images'][0]['url'] ?? $track['images'][0]['url'];
        $this->trackName = $track['name'];

        $artists = $track['artists'] ?? [];
        $artistNames = [];
        foreach ($artists as $artist) {
            $artistNames[] = $artist['name'];
        }

        $this->artistNames = implode(', ', $artistNames);
    }
}
