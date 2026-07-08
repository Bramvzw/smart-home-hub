<?php

/*
 * News sources are generic by default so a fresh install shows reachable,
 * non-personal feeds. To curate your own set without editing this file, point
 * NEWS_FEEDS_FILE at a JSON file shaped like:
 *   { "topics": { "key": "Label", ... },
 *     "feeds":  [ { "key": "...", "topic": "key", "label": "...", "url": "..." } ] }
 * NEWS_KEYWORDS is a comma-separated watch list for keyword push alerts.
 */

$defaultTopics = [
    'technology' => 'Technology',
    'world' => 'World',
    'science' => 'Science',
];

$defaultFeeds = [
    ['key' => 'hacker-news', 'topic' => 'technology', 'label' => 'Hacker News', 'url' => 'https://hnrss.org/frontpage'],
    ['key' => 'ars-technica', 'topic' => 'technology', 'label' => 'Ars Technica', 'url' => 'https://feeds.arstechnica.com/arstechnica/index'],
    ['key' => 'bbc-world', 'topic' => 'world', 'label' => 'BBC World', 'url' => 'https://feeds.bbci.co.uk/news/world/rss.xml'],
    ['key' => 'nasa', 'topic' => 'science', 'label' => 'NASA', 'url' => 'https://www.nasa.gov/feed/'],
];

$topics = $defaultTopics;
$feeds = $defaultFeeds;

$feedsFile = env('NEWS_FEEDS_FILE');
if (is_string($feedsFile) && $feedsFile !== '' && is_file($feedsFile)) {
    $custom = json_decode((string) file_get_contents($feedsFile), true);
    if (is_array($custom)) {
        $topics = isset($custom['topics']) && is_array($custom['topics']) ? $custom['topics'] : $topics;
        $feeds = isset($custom['feeds']) && is_array($custom['feeds']) ? $custom['feeds'] : $feeds;
    }
}

$keywords = array_values(array_filter(array_map(
    trim(...),
    explode(',', (string) env('NEWS_KEYWORDS', '')),
)));

return [
    'refresh_minutes' => env('NEWS_REFRESH_MINUTES', 30),
    'items_per_topic' => env('NEWS_ITEMS_PER_TOPIC', 6),
    'retention_days' => env('NEWS_RETENTION_DAYS', 7),
    'request_timeout' => env('NEWS_REQUEST_TIMEOUT', 10),
    'timezone' => env('NEWS_TIMEZONE', 'Europe/Amsterdam'),

    'topics' => $topics,
    'feeds' => $feeds,
    'keywords' => $keywords,
];
