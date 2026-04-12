<?php

namespace Modules\Spotify\View\ViewModels;

class UpcomingTrackView
{
    public string $trackImage;
    public string $trackName;
    public string $artistNames;

    public function __construct(?array $track = null)
    {
        if ($track === null) {
            $this->trackImage = '/images/no-track.webp';
            $this->trackName = '';
            $this->artistNames = '';
            return;
        }

        $this->trackImage = $track['album']['images'][0]['url'] ?? ($track['images'][0]['url'] ?? '/images/no-track.webp');
        $this->trackName = $track['name'] ?? '';

        $artists = $track['artists'] ?? [];
        $artistNames = [];
        foreach ($artists as $artist) {
            $artistNames[] = $artist['name'];
        }

        $this->artistNames = implode(', ', $artistNames);
    }
}
