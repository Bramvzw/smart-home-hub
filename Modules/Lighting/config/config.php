<?php

return [
    'name' => 'Lighting',

    /*
     * Provider credentials. Secrets are read here only and must never be
     * rendered in views, logged, or exposed in exception messages.
     */
    'tuya' => [
        'client_id' => env('TUYA_CLIENT_ID', ''),
        'client_secret' => env('TUYA_CLIENT_SECRET', ''),
        'region' => env('TUYA_REGION', 'eu'),
        'uid' => env('TUYA_APP_UID', ''),
    ],

    'govee' => [
        'api_key' => env('GOVEE_API_KEY', ''),
        'model_cache_ttl' => (int) env('GOVEE_MODEL_CACHE_TTL', 300),
        'control_retries' => (int) env('GOVEE_CONTROL_RETRIES', 2),
        'command_pause_ms' => (int) env('GOVEE_COMMAND_PAUSE_MS', 160),
    ],

    'presets' => [
        // 'off' first: it is the most-used preset on the kiosk.
        'off' => [
            'label' => 'All off',
            'power' => false,
        ],
        'bright' => [
            'label' => 'Bright',
            'power' => true,
            'brightness' => 100,
            'color' => '#f5f7ff',
        ],
        'cozy' => [
            'label' => 'Cozy',
            'power' => true,
            'brightness' => 72,
            'color' => '#ffc26b',
        ],
        'movie' => [
            'label' => 'Movie',
            'power' => true,
            'brightness' => 42,
            'color' => '#7f96ff',
        ],
        'night' => [
            'label' => 'Night',
            'power' => true,
            'brightness' => 1,
            'color' => '#ff8559',
        ],
        'night_light' => [
            'label' => 'Night light',
            'power' => true,
            'brightness' => 1,
            'color' => '#ff8559',
            'target_name_contains' => ['strip'],
        ],
    ],

    // Light-state cache lifetime in seconds (polling + cache, no realtime push).
    'cache_ttl' => (int) env('LIGHTING_CACHE_TTL', 30),

    // Network timeout per provider request, in seconds.
    'request_timeout' => (int) env('LIGHTING_REQUEST_TIMEOUT', 10),

    // Serialise writes so quick UI actions cannot interleave provider commands.
    'control_lock_ttl' => (int) env('LIGHTING_CONTROL_LOCK_TTL', 20),
    'control_lock_wait' => (int) env('LIGHTING_CONTROL_LOCK_WAIT', 8),
];
