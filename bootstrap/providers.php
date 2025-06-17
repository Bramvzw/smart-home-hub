<?php

use Modules\Spotify\Providers\EventServiceProvider as SpotifyEventServiceProvider;
use Modules\Spotify\Providers\RouteServiceProvider as SpotifyRouteServiceProvider;
use Modules\Spotify\Providers\SpotifyServiceProvider;
use Modules\Tasks\Providers\EventServiceProvider as TasksEventServiceProvider;
use Modules\Tasks\Providers\RouteServiceProvider as TasksRouteServiceProvider;
use Modules\Tasks\Providers\TasksServiceProvider;

return [
    SpotifyServiceProvider::class,
    SpotifyEventServiceProvider::class,
    SpotifyRouteServiceProvider::class,
    TasksServiceProvider::class,
    TasksEventServiceProvider::class,
    TasksRouteServiceProvider::class
];
