<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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
            try {
                return Crypt::decryptString($cached);
            } catch (DecryptException) {
                // Legacy plaintext entry: discard it and fetch a fresh token below.
                Cache::forget(self::CACHE_KEY);
            }
        }

        $result = $this->client->getToken();
        $token = (string) ($result['access_token'] ?? '');

        // Tuya tokens last ~2h; refresh a minute early. Stored encrypted so the
        // cache backend never holds a usable credential at rest.
        $ttl = max(60, (int) ($result['expire_time'] ?? 7200) - 60);
        Cache::put(self::CACHE_KEY, Crypt::encryptString($token), $ttl);

        return $token;
    }
}
