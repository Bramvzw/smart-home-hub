<?php

namespace Modules\Spotify\View\ViewModels;

class PlaylistView
{
    public string $id;
    public string $name;
    public string $imageUrl;
    public string $externalUrl;

    public function __construct($playlist)
    {
        $this->id = $playlist['id'];
        $this->name = $playlist['name'];
        $this->imageUrl = $playlist['images'][0]['url'];
        $this->externalUrl = $playlist['uri'];
    }
}
