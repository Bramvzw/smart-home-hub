<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Transport for the Govee Developer API. The API key travels in the
 * Govee-API-Key header and is never included in thrown messages.
 */
class GoveeApiClient
{
    private const BASE = 'https://developer-api.govee.com';

    private static float $lastControlAt = 0.0;

    public function devices(): array
    {
        return $this->result(
            $this->http()->get(self::BASE.'/v1/devices')
        );
    }

    public function state(string $device, string $model): array
    {
        return $this->result(
            $this->http()->get(self::BASE.'/v1/devices/state', ['device' => $device, 'model' => $model])
        );
    }

    public function control(string $device, string $model, string $name, mixed $value): void
    {
        $attempts = max(1, (int) config('lighting.govee.control_retries', 2));
        $lastException = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $this->waitForControlWindow();

                $this->result(
                    $this->http()->put(self::BASE.'/v1/devices/control', [
                        'device' => $device,
                        'model' => $model,
                        'cmd' => ['name' => $name, 'value' => $value],
                    ])
                );

                $this->rememberControlSent();

                return;
            } catch (Throwable $exception) {
                $lastException = $exception;
                $this->rememberControlSent();
            }
        }

        if ($lastException instanceof RuntimeException) {
            throw $lastException;
        }

        throw new RuntimeException('Govee API request failed.');
    }

    private function http()
    {
        return Http::timeout((int) config('lighting.request_timeout', 10))
            ->withHeaders(['Govee-API-Key' => (string) config('lighting.govee.api_key')]);
    }

    private function result($response): array
    {
        $data = $response->json();
        $code = is_array($data) ? ($data['code'] ?? $response->status()) : $response->status();

        if ((int) $code !== 200) {
            // Only the provider's own code — never the API key.
            throw new RuntimeException("Govee API request failed (code {$code}).");
        }

        return $data['data'] ?? [];
    }

    private function waitForControlWindow(): void
    {
        $pauseMs = max(0, (int) config('lighting.govee.command_pause_ms', 160));

        if ($pauseMs === 0 || self::$lastControlAt === 0.0) {
            return;
        }

        $elapsedMs = (microtime(true) - self::$lastControlAt) * 1000;
        $remainingMs = $pauseMs - $elapsedMs;

        if ($remainingMs > 0) {
            usleep((int) round($remainingMs * 1000));
        }
    }

    private function rememberControlSent(): void
    {
        self::$lastControlAt = microtime(true);
    }
}
