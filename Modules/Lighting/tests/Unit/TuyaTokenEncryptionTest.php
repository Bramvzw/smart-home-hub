<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Mockery;
use Modules\Lighting\Services\Providers\TuyaApiClient;
use Modules\Lighting\Services\Providers\TuyaTokenService;
use Tests\TestCase;

class TuyaTokenEncryptionTest extends TestCase
{
    private const CACHE_KEY = 'lighting:tuya:token';

    public function test_a_fresh_token_is_stored_encrypted_at_rest(): void
    {
        Cache::forget(self::CACHE_KEY);

        $client = Mockery::mock(TuyaApiClient::class);
        $client->expects('getToken')->andReturns(['access_token' => 'tuya-token-123', 'expire_time' => 7200]);

        $token = (new TuyaTokenService($client))->accessToken();

        $this->assertSame('tuya-token-123', $token);

        $stored = Cache::get(self::CACHE_KEY);
        $this->assertIsString($stored);
        $this->assertNotSame('tuya-token-123', $stored);
        $this->assertStringNotContainsString('tuya-token-123', $stored);
        $this->assertSame('tuya-token-123', Crypt::decryptString($stored));
    }

    public function test_a_cached_encrypted_token_is_returned_without_calling_tuya(): void
    {
        Cache::put(self::CACHE_KEY, Crypt::encryptString('cached-token'), 60);

        $client = Mockery::mock(TuyaApiClient::class);
        $client->expects('getToken')->never();

        $this->assertSame('cached-token', (new TuyaTokenService($client))->accessToken());
    }

    public function test_a_legacy_plaintext_cache_entry_is_discarded_and_refreshed(): void
    {
        Cache::put(self::CACHE_KEY, 'legacy-plaintext-token', 60);

        $client = Mockery::mock(TuyaApiClient::class);
        $client->expects('getToken')->andReturns(['access_token' => 'fresh-token', 'expire_time' => 7200]);

        $this->assertSame('fresh-token', (new TuyaTokenService($client))->accessToken());
        $this->assertSame('fresh-token', Crypt::decryptString((string) Cache::get(self::CACHE_KEY)));
    }
}
