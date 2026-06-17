<?php

use App\Providers\DashboardServiceProvider;
use Modules\Calendar\Providers\CalendarServiceProvider;
use Modules\FindHub\Providers\FindHubServiceProvider;
use Modules\Lighting\Providers\LightingServiceProvider;
use Modules\PhonePing\Providers\PhonePingServiceProvider;
use Modules\Spotify\Providers\SpotifyServiceProvider;
use Modules\Tasks\Providers\TasksServiceProvider;

return [
    DashboardServiceProvider::class,
    SpotifyServiceProvider::class,
    TasksServiceProvider::class,
    CalendarServiceProvider::class,
    LightingServiceProvider::class,
    PhonePingServiceProvider::class,
    FindHubServiceProvider::class,
];
