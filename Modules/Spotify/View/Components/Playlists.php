<?php

namespace Modules\Spotify\View\Components;

use Illuminate\View\Component;

class Playlists extends Component
{
    /**
     * @param  list<\Modules\Spotify\View\ViewModels\PlaylistView>  $playlists
     */
    public function __construct(
        public array $playlists = [],
    ) {}

    public function render()
    {
        return view('spotify::components.playlists', [
            'playlists' => $this->playlists,
        ]);
    }
}
