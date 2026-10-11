<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\Providers\Concerns\HandlesProviderResponse;
use RuntimeException;
use Throwable;

/**
 * Low-level signed transport for the Tuya Cloud OpenAPI. Handles request
 * signing (HMAC-SHA256) only — token caching lives in TuyaTokenService.
 *
 * Credentials and the access token are never included in thrown messages.
 */
class TuyaApiClient
{
    use HandlesProviderResponse;

    public function getToken(): array
    {
        $path = '/v1.0/token?grant_type=1';

        return $this->result(
            Http::timeout($this->timeout())
                ->withHeaders($this->headers('GET', $path, '', ''))
                ->get($this->baseUrl().$path)
        );
    }

    public function get(string $path, string $accessToken): array
    {
        return $this->result(
            Http::timeout($this->timeout())
                ->withHeaders($this->headers('GET', $path, '', $accessToken))
                ->get($this->baseUrl().$path)
        );
    }

    public function post(string $path, array $body, string $accessToken): array
    {
        $json = $this->encode($body);

        return $this->result(
            Http::timeout($this->timeout())
                ->withHeaders($this->headers('POST', $path, $json, $accessToken) + ['Content-Type' => 'application/json'])
                ->withBody($json, 'application/json')
                ->post($this->baseUrl().$path)
        );
    }

    /**
     * Send several signed POSTs concurrently (group writes to multiple devices).
     * Each request is signed on its own; one failing never affects the others.
     *
     * @param  array<string, array{path: string, body: array}>  $requests  keyed by the caller's id
     * @return list<string> keys whose request failed
     */
    public function postConcurrently(array $requests, string $accessToken): array
    {
        if ($requests === []) {
            return [];
        }

        $responses = Http::pool(function (Pool $pool) use ($requests, $accessToken): array {
            $pending = [];
            foreach ($requests as $key => $request) {
                $json = $this->encode($request['body']);
                $pending[] = $pool->as((string) $key)
                    ->timeout($this->timeout())
                    ->withHeaders($this->headers('POST', $request['path'], $json, $accessToken) + ['Content-Type' => 'application/json'])
                    ->withBody($json, 'application/json')
                    ->post($this->baseUrl().$request['path']);
            }

            return $pending;
        });

        $failed = [];
        foreach (array_keys($requests) as $key) {
            $response = $responses[(string) $key] ?? null;

            try {
                // Pool entries hold a Throwable on connection failure.
                if (! $response instanceof Response) {
                    throw new RuntimeException('Tuya API request failed.');
                }
                $this->result($response);
            } catch (Throwable) {
                $failed[] = (string) $key;
            }
        }

        return $failed;
    }

    private function encode(array $body): string
    {
        return (string) json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function baseUrl(): string
    {
        $region = strtolower(trim((string) config('lighting.tuya.region', 'eu')));

        return match ($region) {
            'we', 'weu', 'eu-west', 'west-eu', 'western-europe' => 'https://openapi-weaz.tuyaeu.com',
            'us' => 'https://openapi.tuyaus.com',
            'us-east', 'ue', 'ueaz' => 'https://openapi-ueaz.tuyaus.com',
            'cn', 'china' => 'https://openapi.tuyacn.com',
            'in', 'india' => 'https://openapi.tuyain.com',
            default => 'https://openapi.tuyaeu.com', // Central Europe (eu / ce)
        };
    }

    private function timeout(): int
    {
        return (int) config('lighting.request_timeout', 10);
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $method, string $path, string $body, string $accessToken): array
    {
        $clientId = (string) config('lighting.tuya.client_id');
        $secret = (string) config('lighting.tuya.client_secret');
        $t = (string) (int) round(microtime(true) * 1000);

        $stringToSign = $method."\n".hash('sha256', $body)."\n\n".$path;
        $signStr = $clientId.$accessToken.$t.$stringToSign;
        $sign = strtoupper(hash_hmac('sha256', $signStr, $secret));

        $headers = [
            'client_id' => $clientId,
            'sign' => $sign,
            't' => $t,
            'sign_method' => 'HMAC-SHA256',
        ];

        if ($accessToken !== '') {
            $headers['access_token'] = $accessToken;
        }

        return $headers;
    }

    private function result($response): array
    {
        return $this->extractProviderResult(
            $response,
            'Tuya',
            static fn (mixed $data, mixed $code): bool => is_array($data) && ($data['success'] ?? false),
            static function (mixed $data): array {
                // Command endpoints return `result: true`; normalise to an array.
                $result = $data['result'] ?? [];

                return is_array($result) ? $result : [];
            },
        );
    }
}
