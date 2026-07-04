<?php

return [
    'url' => env('HUB_NTFY_URL', env('NTFY_URL', 'https://ntfy.sh')),
    'topic' => env('HUB_NTFY_TOPIC', env('NTFY_TOPIC', '')),
    'token' => env('HUB_NTFY_TOKEN', env('NTFY_TOKEN', '')),
    'timeout' => env('HUB_NTFY_TIMEOUT', 10),

    // When true, notifications are logged instead of pushed — protects a dev
    // machine that shares real topics/credentials with production.
    'dry_run' => (bool) env('NTFY_DRY_RUN', false),
];
