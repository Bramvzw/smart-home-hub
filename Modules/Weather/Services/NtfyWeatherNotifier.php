<?php

namespace Modules\Weather\Services;

use App\Services\Ntfy\HubNotifier;
use Illuminate\Http\Client\RequestException;

class NtfyWeatherNotifier
{
    public function __construct(private readonly HubNotifier $notifier) {}

    public function isConfigured(): bool
    {
        return $this->notifier->isConfigured();
    }

    /** @throws RequestException */
    public function send(string $title, string $message): void
    {
        $this->notifier->sendWithOptions($title, $message, tags: 'umbrella,cloud_with_rain', priority: '4');
    }
}
