<?php

namespace Modules\Spotify\View\ViewModels;

class TrackDetailsView
{
    public string $trackImage;
    public string $trackName;
    public string $artistNames;
    public string $albumName;

    public function __construct(array $playbackState = [])
    {
        if (empty($playbackState) || empty($playbackState['item'])) {
            $this->trackImage = '/images/no-track.webp';
            $this->trackName = '';
            $this->artistNames = '';
            $this->albumName = '';
            return;
        }

        $item = $playbackState['item'];
        $itemType = $item['type'] ?? 'track';

        if ($itemType === 'episode') {
            // Handle podcast episode
            $this->trackImage = $item['images'][0]['url'] ?? '/images/no-track.webp';
            $this->trackName = $item['name'];
            $this->artistNames = $item['show']['publisher'] ?? '';
            $this->albumName = $item['show']['name'] ?? '';
        } else {
            // Handle music track
            $this->trackImage = $item['album']['images'][0]['url'] ?? '/images/no-track.webp';
            $this->trackName = $item['name'];
            $this->artistNames = collect($item['artists'])->pluck('name')->join(', ');
            $this->albumName = $item['album']['name'];
        }
    }
}
