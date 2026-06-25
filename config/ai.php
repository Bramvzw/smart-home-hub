<?php

return [
    'anthropic' => [
        'api_key' => env('HUB_AI_ANTHROPIC_API_KEY', env('ANTHROPIC_API_KEY', '')),
        'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1'),
        'version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
        'anthropic_beta' => env('ANTHROPIC_BETA'),
    ],
];
