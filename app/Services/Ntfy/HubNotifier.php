<?php

namespace App\Services\Ntfy;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class HubNotifier
{
    public function __construct(
        private readonly string $url,
        private readonly string $topic,
        private readonly string $token,
        private readonly int $timeout = 10,
    ) {}

    public function isConfigured(): bool
    {
        return $this->topic !== '';
    }

    /** @throws RequestException */
    public function send(string $title, string $message): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        Http::withHeaders(array_merge($this->headers(), [
                'X-Title' => $title,
                'X-Priority' => '4',
                'X-Tags' => 'newspaper',
            ]))
            ->timeout($this->timeout)
            ->withBody($message, 'text/plain')
            ->post("{$this->url}/{$this->topic}")
            ->throw();
    }

    private function headers(): array
    {
        if ($this->token === '') {
            return [];
        }

        return ['Authorization' => "Bearer {$this->token}"];
    }
}
