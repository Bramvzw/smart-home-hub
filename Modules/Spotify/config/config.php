<?php

return [
    'name' => 'Spotify',

    'request_timeout' => (int) env('SPOTIFY_REQUEST_TIMEOUT', 10),
    'cache_ttl' => (int) env('SPOTIFY_CACHE_TTL', 300),
];
