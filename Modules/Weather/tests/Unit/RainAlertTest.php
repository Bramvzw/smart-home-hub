<?php

namespace Modules\Weather\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Weather\Actions\CheckRainForecast;
use Modules\Weather\Actions\CheckWindForecast;
use Modules\Weather\Actions\SendDailyWeatherSummary;
use Modules\Weather\Services\WeatherService;
use Tests\TestCase;

class RainAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        config([
            'weather.location.label' => 'Herxen 17, Wijhe',
            'weather.location.latitude' => 52.42632587203681,
            'weather.location.longitude' => 6.132287777181066,
            'weather.location.timezone' => 'Europe/Amsterdam',
            'weather.rain.window_hours' => 3,
            'weather.rain.probability_threshold' => 50,
            'weather.rain.precipitation_threshold_mm' => 0,
            'weather.rain.cooldown_seconds' => 3600,
            'weather.rain.alert_start_hour' => 7,
            'weather.rain.alert_end_hour' => 23,
            'weather.wind.threshold_kmh' => 50,
            'weather.wind.cooldown_seconds' => 3600,
            'weather.daily_summary.enabled' => true,
            'weather.ntfy.url' => 'https://ntfy.sh',
            'weather.ntfy.topic' => 'weather-topic',
            'weather.ntfy.token' => '',
            'weather.cache_ttl' => 900,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_rain_detection_uses_precipitation_or_probability(): void
    {
        $this->fakeForecast(
            precipitation: [0, 0, 0.1, 0],
            probability: [10, 55, 10, 20],
        );

        $forecast = app(WeatherService::class)->forecast(CarbonImmutable::now('Europe/Amsterdam'));
        $rainyBlocks = app(WeatherService::class)->rainyBlocks($forecast, CarbonImmutable::now('Europe/Amsterdam'));

        $this->assertCount(2, $rainyBlocks);
        $this->assertSame('11:00', $rainyBlocks[0]->time->format('H:i'));
        $this->assertSame('12:00', $rainyBlocks[1]->time->format('H:i'));
    }

    public function test_check_sends_one_ntfy_notification_per_rain_period(): void
    {
        $this->fakeForecast(
            precipitation: [0, 0.2, 0, 0],
            probability: [10, 40, 20, 10],
        );

        $first = app(CheckRainForecast::class)(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));
        $second = app(CheckRainForecast::class)(CarbonImmutable::parse('2026-06-23 11:00:00', 'Europe/Amsterdam'));

        $this->assertTrue($first->notified);
        $this->assertSame('sent', $first->status);
        $this->assertFalse($second->notified);
        $this->assertSame('already_notified', $second->status);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'ntfy.sh/weather-topic')
            && str_contains((string) $request->body(), 'Start: 11:00'));
    }

    public function test_rain_notification_includes_start_duration_and_intensity(): void
    {
        $this->fakeForecast(
            precipitation: [0, 1.2, 0.8, 0],
            probability: [10, 60, 55, 10],
        );

        app(CheckRainForecast::class)(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ntfy.sh/weather-topic')
            && str_contains((string) $request->body(), 'Start: 11:00')
            && str_contains((string) $request->body(), 'Expected duration: 2 hours')
            && str_contains((string) $request->body(), 'Intensity: Moderate rain'));
    }

    public function test_wind_check_sends_ntfy_notification_when_threshold_is_reached(): void
    {
        $this->fakeForecast(
            precipitation: [0, 0, 0, 0],
            probability: [10, 10, 10, 10],
            windSpeed: [18, 52, 48, 20],
            windGusts: [28, 64, 57, 30],
        );

        $result = app(CheckWindForecast::class)(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        $this->assertTrue($result->notified);
        $this->assertSame('sent', $result->status);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ntfy.sh/weather-topic')
            && str_contains((string) $request->body(), 'First strong wind block: 11:00')
            && str_contains((string) $request->body(), 'Max gust: 64 km/h'));
    }

    public function test_daily_summary_sends_today_and_tomorrow(): void
    {
        $this->fakeForecast(
            precipitation: [0, 0, 0, 0],
            probability: [10, 10, 10, 10],
        );

        $result = app(SendDailyWeatherSummary::class)(CarbonImmutable::parse('2026-06-23 07:15:00', 'Europe/Amsterdam'));

        $this->assertTrue($result->notified);
        $this->assertSame('sent', $result->status);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'ntfy.sh/weather-topic')
            && str_contains((string) $request->body(), 'Today:')
            && str_contains((string) $request->body(), 'Tomorrow:'));
    }

    public function test_check_does_not_notify_outside_alert_hours(): void
    {
        config(['weather.rain.alert_start_hour' => 11]);

        $this->fakeForecast(
            precipitation: [0, 0.2, 0, 0],
            probability: [10, 40, 20, 10],
        );

        $result = app(CheckRainForecast::class)(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        $this->assertFalse($result->notified);
        $this->assertSame('outside_hours', $result->status);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'ntfy.sh/weather-topic'));
    }

    /**
     * @param  list<float>  $precipitation
     * @param  list<int>  $probability
     */
    private function fakeForecast(array $precipitation, array $probability, ?array $windSpeed = null, ?array $windGusts = null): void
    {
        $windSpeed ??= [18, 20, 22, 21];
        $windGusts ??= [28, 30, 32, 31];

        Http::fake([
            '*api.open-meteo.com*' => Http::response([
                'current' => [
                    'temperature_2m' => 18.4,
                    'precipitation' => 0,
                    'weather_code' => 3,
                    'wind_speed_10m' => $windSpeed[0],
                    'wind_gusts_10m' => $windGusts[0],
                ],
                'hourly' => [
                    'time' => [
                        '2026-06-23T10:00',
                        '2026-06-23T11:00',
                        '2026-06-23T12:00',
                        '2026-06-23T13:00',
                    ],
                    'temperature_2m' => [18.2, 18.1, 17.8, 17.5],
                    'precipitation' => $precipitation,
                    'precipitation_probability' => $probability,
                    'weather_code' => [3, 61, 61, 2],
                    'wind_speed_10m' => $windSpeed,
                    'wind_gusts_10m' => $windGusts,
                ],
                'daily' => [
                    'time' => ['2026-06-23', '2026-06-24'],
                    'weather_code' => [61, 2],
                    'temperature_2m_max' => [21.2, 23.1],
                    'temperature_2m_min' => [14.8, 15.2],
                    'precipitation_sum' => [2.4, 0.1],
                    'precipitation_probability_max' => [70, 20],
                    'wind_speed_10m_max' => [max($windSpeed), 18],
                    'wind_gusts_10m_max' => [max($windGusts), 26],
                ],
            ]),
            '*ntfy.sh*' => Http::response('', 200),
        ]);
    }
}
