<?php

return [
    'generate_day' => env('RECIPES_DAY', 'friday'),
    'generate_time' => env('RECIPES_TIME', '18:00'),
    'recipe_count' => env('RECIPES_COUNT', 5),
    'servings' => env('RECIPES_SERVINGS', 2),
    'stores' => ['ah', 'lidl'],
    'request_timeout' => env('RECIPES_TIMEOUT', 15),
    'ai' => [
        'model' => env('RECIPES_MODEL', env('BRIEFING_MODEL', 'claude-sonnet-4-6')),
        'max_tokens' => env('RECIPES_MAX_TOKENS', 2000),
        'temperature' => env('RECIPES_TEMPERATURE', 0.45),
    ],
    'sources' => [
        'ah' => [
            'anonymous_token_url' => env('RECIPES_AH_TOKEN_URL', 'https://api.ah.nl/mobile-auth/v1/auth/token/anonymous'),
            'offers_url' => env('RECIPES_AH_OFFERS_URL', 'https://api.ah.nl/mobile-services/v2/bonuspage'),
            'client_id' => env('RECIPES_AH_CLIENT_ID', 'appie'),
        ],
        'lidl' => [
            'offers_url' => env('RECIPES_LIDL_OFFERS_URL', 'https://www.lidl.nl/q/api/gridboxes/NL/nl'),
        ],
    ],
];
