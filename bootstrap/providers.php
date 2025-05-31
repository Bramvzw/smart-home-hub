<?php

use Modules\Spotify\Providers\EventServiceProvider;
use Modules\Spotify\Providers\RouteServiceProvider;
use Modules\Spotify\Providers\SpotifyServiceProvider;

return [
    SpotifyServiceProvider::class,
    EventServiceProvider::class,
    RouteServiceProvider::class
];
