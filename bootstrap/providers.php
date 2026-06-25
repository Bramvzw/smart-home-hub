<?php

use App\Providers\DashboardServiceProvider;
use Modules\Briefing\Providers\BriefingServiceProvider;
use Modules\Calendar\Providers\CalendarServiceProvider;
use Modules\Deals\Providers\DealsServiceProvider;
use Modules\Entertainment\Providers\EntertainmentServiceProvider;
use Modules\Lighting\Providers\LightingServiceProvider;
use Modules\News\Providers\NewsServiceProvider;
use Modules\PhonePing\Providers\PhonePingServiceProvider;
use Modules\Planner\Providers\PlannerServiceProvider;
use Modules\Printer\Providers\PrinterServiceProvider;
use Modules\Recipes\Providers\RecipesServiceProvider;
use Modules\Spotify\Providers\SpotifyServiceProvider;
use Modules\Tasks\Providers\TasksServiceProvider;
use Modules\Weather\Providers\WeatherServiceProvider;

return [
    DashboardServiceProvider::class,
    SpotifyServiceProvider::class,
    TasksServiceProvider::class,
    CalendarServiceProvider::class,
    LightingServiceProvider::class,
    DealsServiceProvider::class,
    EntertainmentServiceProvider::class,
    NewsServiceProvider::class,
    BriefingServiceProvider::class,
    RecipesServiceProvider::class,
    PlannerServiceProvider::class,
    PhonePingServiceProvider::class,
    PrinterServiceProvider::class,
    WeatherServiceProvider::class,
];
