<?php

namespace Modules\Weather\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenMeteoClient
{
    private const BASE = 'https://api.open-meteo.com/v1/forecast';

    public function forecast(float $latitude, float $longitude, string $timezone): array
    {
        $response = Http::timeout((int) config('weather.request_timeout', 10))->get(self::BASE, [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => $timezone,
            'forecast_days' => 3,
            'current' => 'temperature_2m,precipitation,weather_code,wind_speed_10m,wind_gusts_10m',
            'hourly' => 'temperature_2m,precipitation,precipitation_probability,weather_code,wind_speed_10m,wind_gusts_10m',
            'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_gusts_10m_max',
            'temperature_unit' => 'celsius',
            'wind_speed_unit' => 'kmh',
            'precipitation_unit' => 'mm',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Open-Meteo responded with HTTP {$response->status()}.");
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['hourly']['time']) || ! is_array($data['hourly']['time'])) {
            throw new RuntimeException('Open-Meteo returned an invalid forecast response.');
        }

        return $data;
    }
}
