<?php

namespace Modules\PhonePing\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class NtfyClient
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
        $request = Http::withHeaders($this->headers())
            ->timeout(10);

        $request->post("{$this->url}/{$this->topic}", [
            'title'    => $title,
            'message'  => $message,
            'priority' => 'urgent',
            'tags'     => ['loud_sound', 'phone'],
        ])->throw();
    }

    private function headers(): array
    {
        if ($this->token === '') {
            return [];
        }

        return ['Authorization' => "Bearer {$this->token}"];
    }
}
