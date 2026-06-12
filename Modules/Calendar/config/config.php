<?php

return [
    'name' => 'Calendar',

    /*
     * Calendar feeds. Preferred: CALENDAR_ICS_FEEDS with one feed per line as
     * "label | colour | url". CALENDAR_ICS_URLS (comma-separated) stays as a
     * single, unlabelled fallback. Secret URLs are read here only and must
     * never be rendered in views, logged, or exposed in exceptions.
     */
    'feeds' => (static function (): array {
        $palette = ['#f2ad66', '#6aa6ff', '#54b896', '#e0838a', '#b58cff', '#e2b35a'];
        $feeds = [];

        $pick = static fn (int $i): string => $palette[$i % count($palette)];

        // Normalise a colour: bare hex (abc / a1b2c3) gets a leading '#' so the
        // value can be written in .env without the comment-triggering '#'.
        $colour = static fn (string $c): string => preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $c) ? '#'.$c : $c;

        // Feeds are separated by a newline or a semicolon; fields by '|'.
        foreach (preg_split('/[\r\n;]+/', (string) env('CALENDAR_ICS_FEEDS', '')) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $url = (string) array_pop($parts);
            if ($url === '') {
                continue;
            }

            $label = $parts[0] ?? '';
            $col = $parts[1] ?? '';

            $feeds[] = [
                'label' => $label !== '' ? $label : 'Agenda',
                'color' => $col !== '' ? $colour($col) : $pick(count($feeds)),
                'url' => $url,
            ];
        }

        if ($feeds === []) {
            $urls = array_values(array_filter(array_map('trim', explode(',', (string) env('CALENDAR_ICS_URLS', '')))));
            foreach ($urls as $i => $url) {
                $feeds[] = ['label' => 'Agenda', 'color' => $pick($i), 'url' => $url];
            }
        }

        return $feeds;
    })(),

    // Cache lifetime for a successful fetch, in seconds (default 15 minutes).
    'cache_ttl' => (int) env('CALENDAR_CACHE_TTL', 900),

    // How many days ahead the calendar list/week view covers.
    'window_days' => (int) env('CALENDAR_WINDOW_DAYS', 7),

    // Network timeout per feed request, in seconds.
    'request_timeout' => (int) env('CALENDAR_REQUEST_TIMEOUT', 10),
];
