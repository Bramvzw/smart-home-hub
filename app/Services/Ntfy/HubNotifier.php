<?php

namespace App\Services\Ntfy;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->sendWithOptions($title, $message, 'newspaper', '4');
    }

    /**
     * Same as send(), but with explicit tags/priority instead of the newspaper/4 default.
     *
     * @throws RequestException
     */
    public function sendWithOptions(string $title, string $message, ?string $tags, string $priority): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        // Dev/test safety net: log instead of pushing to a real phone.
        if ((bool) config('ntfy.dry_run', false)) {
            Log::info('ntfy dry-run: notification suppressed', ['title' => $title, 'message' => $message]);

            return;
        }

        $headers = array_merge($this->headers(), [
            'X-Title' => $title,
            'X-Priority' => $priority,
        ]);

        if ($tags !== null) {
            $headers['X-Tags'] = $tags;
        }

        Http::withHeaders($headers)
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
