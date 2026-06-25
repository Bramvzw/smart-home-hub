<?php

return [
    'region' => env('ENTERTAINMENT_REGION', 'NL'),
    'tmdb' => ['api_key' => env('TMDB_API_KEY', ''), 'timeout' => env('TMDB_TIMEOUT', 10)],
    'concerts' => [
        'sources' => ['ticketmaster', 'bandsintown', 'hedon'],
        'ticketmaster_key' => env('TICKETMASTER_KEY', ''),
        'bandsintown_key' => env('BANDSINTOWN_KEY', ''),
        'hedon_agenda_url' => env('HEDON_AGENDA_URL', ''),
    ],
    'check_time' => env('ENTERTAINMENT_CHECK_TIME', '09:00'),
    'music' => ['include' => ['album', 'single', 'ep'], 'bundle_push' => true, 'since_days' => env('ENTERTAINMENT_MUSIC_SINCE_DAYS', 14)],
    'ai' => ['model' => env('ENTERTAINMENT_MODEL', env('BRIEFING_MODEL', 'claude-sonnet-4-6'))],
];
