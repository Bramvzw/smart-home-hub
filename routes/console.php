<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Modules\Briefing\Actions\GenerateBriefing;
use Modules\Deals\Actions\CheckPrices;
use Modules\Entertainment\Actions\NotifyEntertainment;
use Modules\Entertainment\Actions\RefreshConcerts;
use Modules\Entertainment\Actions\RefreshFilms;
use Modules\Entertainment\Actions\RefreshMusicReleases;
use Modules\News\Actions\CheckNewsKeywords;
use Modules\News\Actions\RefreshFeeds;
use Modules\Planner\Actions\GenerateWeeklyPlan;
use Modules\Recipes\Actions\GenerateRecipes;
use Modules\Tasks\Actions\Recurrences\MaterializeDueMaintenance;
use Modules\Weather\Actions\CheckRainForecast;
use Modules\Weather\Actions\CheckWindForecast;
use Modules\Weather\Actions\SendDailyWeatherSummary;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('weather:check-rain', function () {
    try {
        $result = app(CheckRainForecast::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Weather rain check: failed');

        return 1;
    }

    $this->info(sprintf(
        'Weather rain check: %s%s',
        $result->status,
        $result->notified ? ' (notification sent)' : '',
    ));

    return 0;
})->purpose('Check whether rain is expected and send a ntfy alert when needed');

Artisan::command('weather:check-wind', function () {
    try {
        $result = app(CheckWindForecast::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Weather wind check: failed');

        return 1;
    }

    $this->info(sprintf(
        'Weather wind check: %s%s',
        $result->status,
        $result->notified ? ' (notification sent)' : '',
    ));

    return 0;
})->purpose('Check whether hard wind is expected and send a ntfy alert when needed');

Artisan::command('weather:daily-summary', function () {
    try {
        $result = app(SendDailyWeatherSummary::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Weather daily summary: failed');

        return 1;
    }

    $this->info(sprintf(
        'Weather daily summary: %s%s',
        $result->status,
        $result->notified ? ' (notification sent)' : '',
    ));

    return 0;
})->purpose('Send the daily weather summary through ntfy');

Artisan::command('news:refresh', function () {
    try {
        $result = app(RefreshFeeds::class)();
        $notified = app(CheckNewsKeywords::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('News refresh: failed');

        return 1;
    }

    $this->info(sprintf(
        'News refresh: fetched %d, stored %d, skipped %d, failed %d, notified %d',
        $result->fetched,
        $result->stored,
        count($result->skippedFeeds),
        count($result->failedFeeds),
        $notified,
    ));

    return 0;
})->purpose('Refresh configured RSS/Atom feeds and send news keyword alerts');

Artisan::command('briefing:generate', function () {
    try {
        $briefing = app(GenerateBriefing::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Briefing generation: failed');

        return 1;
    }

    $this->info(sprintf(
        'Briefing generated for %s%s',
        $briefing->date->toDateString(),
        $briefing->is_fallback ? ' (fallback)' : '',
    ));

    return 0;
})->purpose('Generate and send the daily briefing');

Artisan::command('tasks:recurrences-due', function () {
    try {
        $created = app(MaterializeDueMaintenance::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Task recurrences: failed');

        return 1;
    }

    $this->info(sprintf('Task recurrences: created %d maintenance card%s', $created, $created === 1 ? '' : 's'));

    return 0;
})->purpose('Materialize due recurring maintenance tasks');

Artisan::command('recipes:generate', function () {
    try {
        $recipes = app(GenerateRecipes::class)(push: true, refetchOffers: true);
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Recipes generation: failed');

        return 1;
    }

    $this->info(sprintf('Recipes generation: stored %d recipe%s', count($recipes), count($recipes) === 1 ? '' : 's'));

    return 0;
})->purpose('Fetch supermarket offers and generate weekend recipes');

Artisan::command('deals:check-prices', function () {
    try {
        $result = app(CheckPrices::class)();
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Deals price check: failed');

        return 1;
    }

    $this->info(sprintf('Deals price check: checked %d listings, found %d drops', $result->checked, count($result->drops)));

    return 0;
})->purpose('Check confirmed deal listings for price drops');

Artisan::command('entertainment:refresh-films', function () {
    $count = app(RefreshFilms::class)();
    $this->info("Entertainment films: refreshed {$count} candidates");

    return 0;
})->purpose('Refresh film recommendations');

Artisan::command('entertainment:refresh-concerts', function () {
    $count = app(RefreshConcerts::class)();
    $this->info("Entertainment concerts: stored {$count} events");

    return 0;
})->purpose('Refresh concert listings');

Artisan::command('entertainment:refresh-music', function () {
    $count = app(RefreshMusicReleases::class)();
    $this->info("Entertainment music: stored {$count} releases");

    return 0;
})->purpose('Refresh followed-artist releases');

Artisan::command('entertainment:notify', function () {
    $result = app(NotifyEntertainment::class)();
    $this->info(sprintf('Entertainment notify: %d music release(s), %d concert(s)', $result['music'], $result['concerts']));

    return 0;
})->purpose('Send entertainment notifications');

Artisan::command('planner:generate', function () {
    try {
        $plan = app(GenerateWeeklyPlan::class)(push: true);
    } catch (\Throwable $exception) {
        report($exception);
        $this->error('Planner generation: failed');

        return 1;
    }

    $this->info("Planner generated for {$plan->week_key}");

    return 0;
})->purpose('Generate the weekly agenda plan');

Schedule::command('weather:check-rain')
    ->everyThirtyMinutes()
    ->between('7:00', '23:00')
    ->withoutOverlapping();

Schedule::command('weather:check-wind')
    ->everyThirtyMinutes()
    ->between('7:00', '23:00')
    ->withoutOverlapping();

Schedule::command('weather:daily-summary')
    ->dailyAt((string) config('weather.daily_summary.time', '07:15'))
    ->withoutOverlapping();

Schedule::command('news:refresh')
    ->cron(sprintf('*/%d * * * *', max(1, min(59, (int) config('news.refresh_minutes', 30)))))
    ->withoutOverlapping();

Schedule::command('briefing:generate')
    ->dailyAt((string) config('briefing.time', '08:00'))
    ->withoutOverlapping();

Schedule::command('tasks:recurrences-due')
    ->dailyAt('07:00')
    ->withoutOverlapping();

Schedule::command('recipes:generate')
    ->weeklyOn([
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
    ][mb_strtolower((string) config('recipes.generate_day', 'friday'))] ?? 5, (string) config('recipes.generate_time', '18:00'))
    ->withoutOverlapping();

Schedule::command('deals:check-prices')
    ->cron((string) config('deals.check_cron', '0 */3 * * *'))
    ->withoutOverlapping();

Schedule::command('entertainment:refresh-films')
    ->weeklyOn(1, (string) config('entertainment.check_time', '09:00'))
    ->withoutOverlapping();

Schedule::command('entertainment:refresh-concerts')
    ->dailyAt((string) config('entertainment.check_time', '09:00'))
    ->withoutOverlapping();

Schedule::command('entertainment:refresh-music')
    ->dailyAt((string) config('entertainment.check_time', '09:00'))
    ->withoutOverlapping();

Schedule::command('entertainment:notify')
    ->dailyAt('09:15')
    ->withoutOverlapping();

Schedule::command('planner:generate')
    ->weeklyOn([
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
    ][mb_strtolower((string) config('planner.generate.day', 'sunday'))] ?? 0, (string) config('planner.generate.time', '19:00'))
    ->withoutOverlapping();
