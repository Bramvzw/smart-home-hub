<?php

namespace Modules\Weather\View\ViewModels;

use Carbon\CarbonImmutable;
use Modules\Weather\Services\WeatherService;
use Throwable;

class WeatherViewModel
{
    public function __construct(
        private readonly WeatherService $service,
    ) {}

    public function page(): array
    {
        $timezone = (string) config('weather.location.timezone', 'Europe/Amsterdam');
        $now = CarbonImmutable::now($timezone);
        $configured = $this->service->isConfigured();

        $base = [
            'configured' => $configured,
            'failed' => false,
            'forecast' => null,
            'windowBlocks' => [],
            'rainyBlocks' => [],
            'windyBlocks' => [],
            'rainEvent' => null,
            'windEvent' => null,
            'today' => null,
            'tomorrow' => null,
            'locationLabel' => (string) config('weather.location.label', 'Wijhe'),
            'latitude' => (float) config('weather.location.latitude'),
            'longitude' => (float) config('weather.location.longitude'),
            'windowHours' => (int) config('weather.rain.window_hours', 3),
            'probabilityThreshold' => (int) config('weather.rain.probability_threshold', 50),
            'precipitationThresholdMm' => (float) config('weather.rain.precipitation_threshold_mm', 0),
            'windThresholdKmh' => (float) config('weather.wind.threshold_kmh', 50),
            'refreshSeconds' => (int) config('weather.refresh_seconds', 900),
            'alertStartHour' => (int) config('weather.rain.alert_start_hour', 7),
            'alertEndHour' => (int) config('weather.rain.alert_end_hour', 23),
            'notificationsConfigured' => $this->service->notificationsConfigured(),
            'withinAlertHours' => $this->service->withinAlertHours($now),
            'lastAlert' => $this->service->lastAlert(),
            'now' => $now,
        ];

        if (! $configured) {
            return $base;
        }

        try {
            $forecast = $this->service->forecast($now);

            return array_merge($base, [
                'forecast' => $forecast,
                'windowBlocks' => $forecast->hourlyWindow($now, (int) config('weather.rain.window_hours', 3)),
                'rainyBlocks' => $this->service->rainyBlocks($forecast, $now),
                'windyBlocks' => $this->service->windyBlocks($forecast, $now),
                'rainEvent' => $this->service->rainEvent($forecast, $now),
                'windEvent' => $this->service->windEvent($forecast, $now),
                'today' => $forecast->today(),
                'tomorrow' => $forecast->tomorrow(),
            ]);
        } catch (Throwable) {
            return array_merge($base, ['failed' => true]);
        }
    }
}
