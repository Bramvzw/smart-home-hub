<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\Providers\Concerns\HandlesProviderResponse;
use RuntimeException;
use Throwable;

/**
 * Transport for the Govee Developer API. The API key travels in the
 * Govee-API-Key header and is never included in thrown messages.
 */
class GoveeApiClient
{
    use HandlesProviderResponse;

    private const BASE = 'https://developer-api.govee.com';

    private float $lastControlAt = 0.0;

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

    /**
     * Fetch several device states concurrently — state reads are cheap GETs,
     * so the page load pays one round-trip instead of one per lamp.
     *
     * @param  list<array{device: string, model: string}>  $requests
     * @return array<string, array> device id => state result; failed reads are absent
     */
    public function states(array $requests): array
    {
        if ($requests === []) {
            return [];
        }

        $timeout = (int) config('lighting.request_timeout', 10);
        $apiKey = (string) config('lighting.govee.api_key');

        $responses = Http::pool(fn ($pool): array => array_map(
            fn (array $request) => $pool->as($request['device'])
                ->timeout($timeout)
                ->withHeaders(['Govee-API-Key' => $apiKey])
                ->get(self::BASE.'/v1/devices/state', ['device' => $request['device'], 'model' => $request['model']]),
            $requests,
        ));

        $states = [];
        foreach ($responses as $device => $response) {
            try {
                // Pool entries hold a Throwable on connection failure.
                if ($response instanceof \Illuminate\Http\Client\Response) {
                    $states[(string) $device] = $this->result($response);
                }
            } catch (Throwable) {
                // Per-device isolation: a failed read just marks that lamp unreachable.
            }
        }

        return $states;
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
        return $this->extractProviderResult(
            $response,
            'Govee',
            static fn (mixed $data, mixed $code): bool => (int) $code === 200,
            static fn (mixed $data): array => $data['data'] ?? [],
        );
    }

    private function waitForControlWindow(): void
    {
        $pauseMs = max(0, (int) config('lighting.govee.command_pause_ms', 160));

        if ($pauseMs === 0 || $this->lastControlAt === 0.0) {
            return;
        }

        $elapsedMs = (microtime(true) - $this->lastControlAt) * 1000;
        $remainingMs = $pauseMs - $elapsedMs;

        if ($remainingMs > 0) {
            usleep((int) round($remainingMs * 1000));
        }
    }

    private function rememberControlSent(): void
    {
        $this->lastControlAt = microtime(true);
    }
}
