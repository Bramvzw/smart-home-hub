<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Cache;

class TuyaTokenService
{
    private const CACHE_KEY = 'lighting:tuya:token';

    public function __construct(
        private readonly TuyaApiClient $client,
    ) {}

    public function accessToken(): string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $result = $this->client->getToken();
        $token = (string) ($result['access_token'] ?? '');

        // Tuya tokens last ~2h; refresh a minute early.
        $ttl = max(60, (int) ($result['expire_time'] ?? 7200) - 60);
        Cache::put(self::CACHE_KEY, $token, $ttl);

        return $token;
    }
}
