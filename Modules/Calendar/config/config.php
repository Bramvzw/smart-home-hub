<?php

return [
    'name' => 'Calendar',

    // Display timezone for the agenda. The app runs in UTC (see config/app.php);
    // like the other modules, Calendar renders in local time via its own key.
    'timezone' => env('CALENDAR_TIMEZONE', 'Europe/Amsterdam'),

    'window_days' => (int) env('CALENDAR_WINDOW_DAYS', 7),
    'cache_ttl' => (int) env('CALENDAR_CACHE_TTL', 300),
    'request_timeout' => (int) env('CALENDAR_REQUEST_TIMEOUT', 10),

    'work_hours' => ['days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:00'],
    'week_starts' => 'monday',
    // CALENDAR_GENERATE_* are the current names; PLANNER_* fallbacks keep the existing NAS .env working.
    'generate' => ['day' => 'sunday', 'time' => env('CALENDAR_GENERATE_TIME', env('PLANNER_TIME', '19:00'))],
    'default_durations' => ['sport' => 90, 'family' => 150, 'date' => 180],

    // Google Calendar is the single agenda source, powering both the view and the planner.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT', ''),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    'ai' => ['model' => env('CALENDAR_GENERATE_MODEL', env('PLANNER_MODEL', env('BRIEFING_MODEL', 'claude-sonnet-4-6')))],
];
