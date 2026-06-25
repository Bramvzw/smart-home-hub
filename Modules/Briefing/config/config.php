<?php

return [
    'time' => env('BRIEFING_TIME', '08:00'),
    'retention_days' => env('BRIEFING_RETENTION_DAYS', 14),
    'timezone' => env('BRIEFING_TIMEZONE', 'Europe/Amsterdam'),
    'language' => 'nl',
    'tone' => 'informal',
    'length' => 'medium',
    'ntfy_max_length' => env('BRIEFING_NTFY_MAX_LENGTH', 3900),
    'tasks_limit' => env('BRIEFING_TASKS_LIMIT', 3),
    'news_items_per_topic' => env('BRIEFING_NEWS_ITEMS_PER_TOPIC', 2),
    'ai' => [
        'model' => env('BRIEFING_MODEL', 'claude-sonnet-4-6'),
        'max_tokens' => env('BRIEFING_MAX_TOKENS', 700),
        'temperature' => env('BRIEFING_TEMPERATURE', 0.5),
    ],
];
