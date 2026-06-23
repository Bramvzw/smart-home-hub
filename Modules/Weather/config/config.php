<?php

return [
    'name' => 'Weather',

    'location' => [
        'label' => env('WEATHER_LOCATION_LABEL', 'Herxen 17, Wijhe'),
        'latitude' => (float) env('WEATHER_LATITUDE', 52.42632587203681),
        'longitude' => (float) env('WEATHER_LONGITUDE', 6.132287777181066),
        'timezone' => env('WEATHER_TIMEZONE', 'Europe/Amsterdam'),
    ],

    'rain' => [
        'window_hours' => (int) env('WEATHER_RAIN_WINDOW_HOURS', 3),
        'probability_threshold' => (int) env('WEATHER_RAIN_PROBABILITY_THRESHOLD', 50),
        'precipitation_threshold_mm' => (float) env('WEATHER_RAIN_PRECIPITATION_THRESHOLD_MM', 0),
        'cooldown_seconds' => (int) env('WEATHER_RAIN_ALERT_COOLDOWN', 3600),
        'alert_start_hour' => (int) env('WEATHER_ALERT_START_HOUR', 7),
        'alert_end_hour' => (int) env('WEATHER_ALERT_END_HOUR', 23),
    ],

    'wind' => [
        'threshold_kmh' => (float) env('WEATHER_WIND_ALERT_THRESHOLD_KMH', 50),
        'cooldown_seconds' => (int) env('WEATHER_WIND_ALERT_COOLDOWN', 3600),
    ],

    'daily_summary' => [
        'enabled' => filter_var(env('WEATHER_DAILY_SUMMARY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'time' => env('WEATHER_DAILY_SUMMARY_TIME', '07:15'),
    ],

    'ntfy' => [
        'url' => env('WEATHER_NTFY_URL') ?: env('PHONE_PING_NTFY_URL', 'https://ntfy.sh'),
        'topic' => env('WEATHER_NTFY_TOPIC') ?: env('PHONE_PING_NTFY_TOPIC', ''),
        'token' => env('WEATHER_NTFY_TOKEN') ?: env('PHONE_PING_NTFY_TOKEN', ''),
    ],

    'cache_ttl' => (int) env('WEATHER_CACHE_TTL', 900),
    'request_timeout' => (int) env('WEATHER_REQUEST_TIMEOUT', 10),
    'refresh_seconds' => (int) env('WEATHER_REFRESH_SECONDS', 900),
];
