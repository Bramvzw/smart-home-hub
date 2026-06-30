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

// The registration order below is the sidebar menu order (ModuleRegistry builds
// the navigation by iterating modules as they register). DashboardServiceProvider
// stays first; the rest follow a daily-use flow: morning overview → planning →
// home → media → household/hobby → utility.
return [
    DashboardServiceProvider::class,

    // Day start & overview
    BriefingServiceProvider::class,
    WeatherServiceProvider::class,

    // Planning & productivity
    CalendarServiceProvider::class,
    PlannerServiceProvider::class,
    TasksServiceProvider::class,

    // Home
    LightingServiceProvider::class,

    // Media & leisure
    SpotifyServiceProvider::class,
    EntertainmentServiceProvider::class,
    NewsServiceProvider::class,

    // Household & hobby
    RecipesServiceProvider::class,
    DealsServiceProvider::class,
    PrinterServiceProvider::class,

    // Utility
    PhonePingServiceProvider::class,
];
