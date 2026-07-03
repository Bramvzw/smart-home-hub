<?php

return [
    'private_access' => [
        'enabled' => env('PRIVATE_NETWORK_GUARD_ENABLED', true),
        'allowed_cidrs' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('PRIVATE_NETWORK_ALLOWED_CIDRS', '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'))
        ))),
    ],

    // Optional HTTP Basic Auth on top of the private-network guard. Active only
    // when both values are set; leave empty for an unauthenticated LAN kiosk.
    'basic_auth' => [
        'username' => (string) env('HUB_AUTH_USERNAME', ''),
        'password' => (string) env('HUB_AUTH_PASSWORD', ''),
    ],
];
