<?php

namespace Modules\Lighting\Tests\Unit;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Lighting\Actions\ApplyWeatherTriggeredPresets;
use Modules\Lighting\Data\LightingPresetResult;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Data\WeatherPresetTrigger;
use Modules\Lighting\Services\LightingService;
use Modules\Weather\Data\WeatherForecast;
use Modules\Weather\Data\WeatherHour;
use Modules\Weather\Services\WeatherService;
use Tests\TestCase;

class WeatherTriggeredPresetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-23 10:30:00', 'Europe/Amsterdam'));

        config([
            'lighting.presets' => [
                'rain_preset' => [
                    'label' => 'Rain cozy',
                    'power' => true,
                    'brightness' => 72,
                    'color' => '#ffc26b',
                    'weather_trigger' => [
                        'enabled' => true,
                        'rain_probability_min' => 60,
                        'precipitation_min_mm' => 0.1,
                        'temperature_max' => null,
                        'start_time' => '07:00',
                        'end_time' => '23:00',
                    ],
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_preset_is_applied_when_all_conditions_match(): void
    {
        $this->fakeForecast(precipitationMm: 0.5, probability: 80, temperature: 15.0);

        $this->partialMock(LightingService::class, function ($mock): void {
            $mock->shouldReceive('applyPreset')
                ->once()
                ->with('rain_preset')
                ->andReturn(new LightingPresetResult($this->makeLightPreset(), 1, 0, []));
        });

        $applied = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now());

        $this->assertSame(['rain_preset'], $applied);
    }

    public function test_preset_is_not_applied_when_conditions_do_not_match(): void
    {
        // Probability below the configured minimum of 60.
        $this->fakeForecast(precipitationMm: 0.5, probability: 20, temperature: 15.0);

        $this->partialMock(LightingService::class, function ($mock): void {
            $mock->shouldNotReceive('applyPreset');
        });

        $applied = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now());

        $this->assertSame([], $applied);
    }

    public function test_preset_is_not_applied_outside_the_configured_time_window(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-23 23:30:00', 'Europe/Amsterdam'));

        $this->fakeForecast(precipitationMm: 0.5, probability: 80, temperature: 15.0);

        $this->partialMock(LightingService::class, function ($mock): void {
            $mock->shouldNotReceive('applyPreset');
        });

        $applied = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now());

        $this->assertSame([], $applied);
    }

    public function test_preset_is_applied_at_most_once_per_continuous_trigger_period(): void
    {
        $this->fakeForecast(precipitationMm: 0.5, probability: 80, temperature: 15.0);

        $this->partialMock(LightingService::class, function ($mock): void {
            $mock->shouldReceive('applyPreset')
                ->once()
                ->with('rain_preset')
                ->andReturn(new LightingPresetResult($this->makeLightPreset(), 1, 0, []));
        });

        $first = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now());
        $second = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now()->addMinutes(15));

        $this->assertSame(['rain_preset'], $first);
        $this->assertSame([], $second);
    }

    public function test_preset_can_trigger_again_after_the_period_ends_and_matches_again(): void
    {
        $this->partialMock(LightingService::class, function ($mock): void {
            $mock->shouldReceive('applyPreset')
                ->twice()
                ->with('rain_preset')
                ->andReturn(new LightingPresetResult($this->makeLightPreset(), 1, 0, []));
        });

        $this->fakeForecast(precipitationMm: 0.5, probability: 80, temperature: 15.0);
        $first = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now());

        // Rain stops: conditions no longer match, so the "active" marker clears.
        // (Stays within the same forecast hour bucket as the frozen test time.)
        $this->fakeForecast(precipitationMm: 0, probability: 10, temperature: 15.0);
        $cleared = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now()->addMinutes(15));

        // Rain starts again: this is a new trigger period.
        $this->fakeForecast(precipitationMm: 0.5, probability: 80, temperature: 15.0);
        $second = app(ApplyWeatherTriggeredPresets::class)(CarbonImmutable::now()->addMinutes(20));

        $this->assertSame(['rain_preset'], $first);
        $this->assertSame([], $cleared);
        $this->assertSame(['rain_preset'], $second);
    }

    private function makeLightPreset(): LightPreset
    {
        return new LightPreset(
            key: 'rain_preset',
            label: 'Rain cozy',
            power: true,
            brightness: 72,
            color: '#ffc26b',
            targetNameContains: [],
            weatherTrigger: new WeatherPresetTrigger(
                enabled: true,
                rainProbabilityMin: 60,
                precipitationMinMm: 0.1,
                temperatureMax: null,
                startTime: '07:00',
                endTime: '23:00',
            ),
        );
    }

    private function fakeForecast(float $precipitationMm, ?int $probability, ?float $temperature): void
    {
        $now = CarbonImmutable::now('Europe/Amsterdam');
        $currentHour = $now->startOfHour();

        $forecast = new WeatherForecast(
            locationLabel: 'Herxen 17, Wijhe',
            latitude: 52.3676,
            longitude: 4.9041,
            timezone: 'Europe/Amsterdam',
            fetchedAt: $now,
            hours: [
                new WeatherHour(
                    time: $currentHour,
                    temperature: $temperature,
                    precipitationMm: $precipitationMm,
                    precipitationProbability: $probability,
                    weatherCode: 61,
                ),
            ],
            days: [],
            currentTemperature: $temperature,
            currentPrecipitationMm: $precipitationMm,
            currentWeatherCode: 61,
            currentWindSpeedKmh: 10.0,
            currentWindGustsKmh: 15.0,
        );

        $this->mock(WeatherService::class, function ($mock) use ($forecast): void {
            $mock->shouldReceive('forecast')->andReturn($forecast);
        });
    }
}
