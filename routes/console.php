<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
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
