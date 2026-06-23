<?php

namespace Modules\Weather\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class NtfyWeatherNotifier
{
    public function __construct(
        private readonly string $url,
        private readonly string $topic,
        private readonly string $token,
    ) {}

    public function isConfigured(): bool
    {
        return $this->topic !== '';
    }

    /** @throws RequestException */
    public function send(string $title, string $message): void
    {
        Http::withHeaders(array_merge($this->headers(), [
                'X-Title' => $title,
                'X-Priority' => '4',
                'X-Tags' => 'umbrella,cloud_with_rain',
            ]))
            ->timeout(10)
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
