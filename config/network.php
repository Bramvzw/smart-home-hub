<?php

return [
    'private_access' => [
        'enabled' => env('PRIVATE_NETWORK_GUARD_ENABLED', true),
        'allowed_cidrs' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('PRIVATE_NETWORK_ALLOWED_CIDRS', '127.0.0.1/32,::1/128,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16'))
        ))),
    ],
];
