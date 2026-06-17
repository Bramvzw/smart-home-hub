<?php

return [
    'name' => 'Phone Ping',

    'ntfy' => [
        'url'   => env('PHONE_PING_NTFY_URL', 'https://ntfy.sh'),
        'topic' => env('PHONE_PING_NTFY_TOPIC', ''),
        'token' => env('PHONE_PING_NTFY_TOKEN', ''),
    ],

    'cooldown_seconds' => (int) env('PHONE_PING_COOLDOWN', 10),
];
