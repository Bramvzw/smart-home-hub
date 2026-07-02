<?php

namespace Modules\PhonePing\Services;

use App\Services\Ntfy\HubNotifier;
use Illuminate\Http\Client\RequestException;

class NtfyClient
{
    public function __construct(private readonly HubNotifier $notifier) {}

    public function isConfigured(): bool
    {
        return $this->notifier->isConfigured();
    }

    /** @throws RequestException */
    public function send(string $title, string $message): void
    {
        $this->notifier->sendWithOptions($title, $message, tags: null, priority: '5');
    }
}
