<?php

return [
    'refresh_minutes' => env('NEWS_REFRESH_MINUTES', 30),
    'items_per_topic' => env('NEWS_ITEMS_PER_TOPIC', 6),
    'retention_days' => env('NEWS_RETENTION_DAYS', 7),
    'request_timeout' => env('NEWS_REQUEST_TIMEOUT', 10),
    'timezone' => env('NEWS_TIMEZONE', 'Europe/Amsterdam'),

    'topics' => [
        '3d-printing' => '3D-printen & making',
        'dev' => 'Dev & werk',
        'fitness' => 'Fitness & gezondheid',
        'gardening' => 'Tuinieren / moestuin',
        'switch2' => 'Nintendo Switch 2',
    ],

    // Verified 2026-06-25. All3DP returned 403 and PHP.Watch changelog returned 404,
    // so this curated default set uses reachable feeds in the same topics.
    'feeds' => [
        ['key' => 'hackaday', 'topic' => '3d-printing', 'label' => 'Hackaday', 'url' => 'https://hackaday.com/blog/feed/'],
        ['key' => 'prusa-blog', 'topic' => '3d-printing', 'label' => 'Prusa Blog', 'url' => 'https://blog.prusa3d.com/feed/'],
        ['key' => 'laravel-news', 'topic' => 'dev', 'label' => 'Laravel News', 'url' => 'https://feed.laravel-news.com/'],
        ['key' => 'stitcher', 'topic' => 'dev', 'label' => 'Stitcher.io', 'url' => 'https://stitcher.io/rss'],
        ['key' => 'sbs', 'topic' => 'fitness', 'label' => 'Stronger by Science', 'url' => 'https://www.strongerbyscience.com/feed/'],
        ['key' => 'gardeners-world-nl', 'topic' => 'gardening', 'label' => 'Gardeners World NL', 'url' => 'https://www.gardenersworldmagazine.nl/feed/'],
        ['key' => 'nintendolife', 'topic' => 'switch2', 'label' => 'Nintendo Life', 'url' => 'https://www.nintendolife.com/feeds/latest'],
    ],

    'keywords' => ['Bambu', 'Bambu firmware', 'Laravel', 'PHP 8', 'Switch 2'],
];
