<?php

namespace Modules\Weather\Support;

class WeatherCode
{
    public static function label(?int $code): string
    {
        return match ($code) {
            0 => 'Clear',
            1, 2 => 'Partly cloudy',
            3 => 'Cloudy',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',
            61, 63, 65 => 'Rain',
            66, 67 => 'Freezing rain',
            71, 73, 75, 77 => 'Snow',
            80, 81, 82 => 'Showers',
            85, 86 => 'Snow showers',
            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',
            default => 'Unknown',
        };
    }
}
