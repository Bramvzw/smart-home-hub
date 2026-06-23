<?php

namespace Modules\Weather\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        config([
            'weather.location.label' => 'Herxen 17, Wijhe',
            'weather.location.latitude' => 52.42632587203681,
            'weather.location.longitude' => 6.132287777181066,
            'weather.location.timezone' => 'Europe/Amsterdam',
            'weather.ntfy.topic' => 'weather-topic',
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_weather_page_shows_the_rain_window(): void
    {
        $this->fakeForecast();

        $response = $this->get(route('weather.index'));

        $response->assertStatus(200);
        $response->assertSee('Herxen 17, Wijhe');
        $response->assertSee('52,42633');
        $response->assertSee('6,13229');
        $response->assertSee('Rain in');
        $response->assertSee('11:00');
        $response->assertSee('Today');
        $response->assertSee('Tomorrow');
        $response->assertSee('Wind');
        $response->assertSee('Live refresh');
        $response->assertSee('ntfy active');
    }

    public function test_weather_page_handles_provider_failure(): void
    {
        Http::fake([
            '*api.open-meteo.com*' => Http::response(['reason' => 'down'], 503),
        ]);

        $response = $this->get(route('weather.index'));

        $response->assertStatus(200);
        $response->assertSee('Weather data unavailable');
    }

    public function test_weather_page_explains_when_no_rain_alert_is_needed(): void
    {
        $this->fakeForecast(
            precipitation: [0, 0, 0, 0],
            probability: [10, 20, 30, 40],
        );

        $response = $this->get(route('weather.index'));

        $response->assertStatus(200);
        $response->assertSee('No rain alert needed');
        $response->assertSee('No hourly block in the next 3 hours crosses the alert threshold');
        $response->assertSee('more than 0.0 mm or at least 50% probability');
    }

    /**
     * @param  list<float>  $precipitation
     * @param  list<int>  $probability
     */
    private function fakeForecast(array $precipitation = [0, 0.2, 0, 0], array $probability = [10, 40, 20, 10]): void
    {
        Http::fake([
            '*api.open-meteo.com*' => Http::response([
                'current' => [
                    'temperature_2m' => 18.4,
                    'precipitation' => 0,
                    'weather_code' => 3,
                    'wind_speed_10m' => 18,
                    'wind_gusts_10m' => 28,
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
                    'weather_code' => [3, 61, 3, 2],
                    'wind_speed_10m' => [18, 20, 22, 21],
                    'wind_gusts_10m' => [28, 30, 32, 31],
                ],
                'daily' => [
                    'time' => ['2026-06-23', '2026-06-24', '2026-06-25'],
                    'weather_code' => [61, 2, 0],
                    'temperature_2m_max' => [21.2, 23.1, 25.4],
                    'temperature_2m_min' => [14.8, 15.2, 16.1],
                    'precipitation_sum' => [2.4, 0.1, 0.0],
                    'precipitation_probability_max' => [70, 20, 5],
                    'wind_speed_10m_max' => [24, 18, 14],
                    'wind_gusts_10m_max' => [36, 26, 20],
                ],
            ]),
        ]);
    }
}
