<?php

namespace Modules\Spotify\View\ViewModels;

class UpcomingTrackViewModel
{
    public bool $hasTrack;
    public string $trackImage;
    public string $trackName;
    public string $artistNames;

    public function __construct(?array $track = null)
    {
        $track = $this->unwrapTrack($track);
        $this->hasTrack = $track !== null;

        if ($track === null) {
            $this->trackImage = '';
            $this->trackName = 'No upcoming track';
            $this->artistNames = '';
            return;
        }

        $this->trackImage = $this->resolveImageUrl($track);
        $this->trackName = $track['name'] ?? 'Unknown track';
        $this->artistNames = $this->resolveArtistNames($track);
    }

    private function unwrapTrack(?array $track): ?array
    {
        if ($track === null) {
            return null;
        }

        if (isset($track['track']) && is_array($track['track'])) {
            return $track['track'];
        }

        return $track;
    }

    private function resolveImageUrl(array $track): string
    {
        foreach ([
            $track['album']['images'] ?? null,
            $track['images'] ?? null,
            $track['show']['images'] ?? null,
        ] as $images) {
            if (is_array($images) && isset($images[0]['url'])) {
                return $images[0]['url'];
            }
        }

        return '';
    }

    private function resolveArtistNames(array $track): string
    {
        $artists = $track['artists'] ?? ($track['album']['artists'] ?? []);
        $artistNames = [];

        foreach ($artists as $artist) {
            if (isset($artist['name'])) {
                $artistNames[] = $artist['name'];
            }
        }

        if ($artistNames === [] && isset($track['show']['publisher'])) {
            $artistNames[] = $track['show']['publisher'];
        }

        return implode(', ', $artistNames);
    }
}
