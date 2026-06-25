<?php

return [
    'check_cron' => env('DEALS_CHECK', '0 */3 * * *'),
    'retailers' => ['bol', 'amazon', 'tweakers'],
    'request_timeout' => env('DEALS_TIMEOUT', 15),
    'bol' => [
        'enabled' => env('DEALS_BOL', true),
        'client_id' => env('BOL_API_KEY', ''),
        'client_secret' => env('BOL_API_SECRET', ''),
        'token_url' => env('BOL_TOKEN_URL', 'https://login.bol.com/token'),
        'search_url' => env('BOL_SEARCH_URL', 'https://api.bol.com/retailer/products/list'),
        'price_url' => env('BOL_PRICE_URL', 'https://api.bol.com/retailer/offers'),
    ],
    'amazon' => [
        'enabled' => env('DEALS_AMAZON', false),
        'search_url' => env('DEALS_AMAZON_SEARCH_URL', ''),
        'price_url' => env('DEALS_AMAZON_PRICE_URL', ''),
    ],
    'tweakers' => [
        'enabled' => env('DEALS_TWEAKERS', true),
        'search_url' => env('DEALS_TWEAKERS_SEARCH_URL', 'https://tweakers.net/pricewatch/zoeken/?keyword='),
        'price_url' => env('DEALS_TWEAKERS_PRICE_URL', ''),
    ],
];
