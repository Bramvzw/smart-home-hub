<?php

return [
    'work_hours' => ['days' => [1, 2, 3, 4, 5], 'start' => '09:00', 'end' => '17:00'],
    'week_starts' => 'monday',
    'generate' => ['day' => 'sunday', 'time' => env('PLANNER_TIME', '19:00')],
    'default_durations' => ['sport' => 90, 'family' => 150, 'date' => 180],
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID', ''),
        'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
        'redirect' => env('GOOGLE_REDIRECT', ''),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],
    'ai' => ['model' => env('PLANNER_MODEL', env('BRIEFING_MODEL', 'claude-sonnet-4-6'))],
];
