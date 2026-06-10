<?php

return [
    'name' => 'Calendar',

    /*
     * One or more Google Calendar "secret address in iCal format" URLs.
     * Comma-separated in the environment. The secret URL is read here only and
     * must never be rendered in views, logged, or exposed in exceptions.
     */
    'ics_urls' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CALENDAR_ICS_URLS', ''))
    ))),

    // Cache lifetime for a successful fetch, in seconds (default 15 minutes).
    'cache_ttl' => (int) env('CALENDAR_CACHE_TTL', 900),

    // How many days ahead the calendar list/week view covers.
    'window_days' => (int) env('CALENDAR_WINDOW_DAYS', 7),

    // Network timeout per feed request, in seconds.
    'request_timeout' => (int) env('CALENDAR_REQUEST_TIMEOUT', 10),
];
