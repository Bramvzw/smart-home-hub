<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Low-level signed transport for the Tuya Cloud OpenAPI. Handles request
 * signing (HMAC-SHA256) only — token caching lives in TuyaTokenService.
 *
 * Credentials and the access token are never included in thrown messages.
 */
class TuyaApiClient
{
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
        $json = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $this->result(
            Http::timeout($this->timeout())
                ->withHeaders($this->headers('POST', $path, $json, $accessToken) + ['Content-Type' => 'application/json'])
                ->withBody($json, 'application/json')
                ->post($this->baseUrl().$path)
        );
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
        $data = $response->json();

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            // Only the provider's own code/msg — never our credentials or token.
            $code = is_array($data) ? ($data['code'] ?? $response->status()) : $response->status();
            throw new RuntimeException("Tuya API request failed (code {$code}).");
        }

        // Command endpoints return `result: true`; normalise to an array.
        $result = $data['result'] ?? [];

        return is_array($result) ? $result : [];
    }
}
