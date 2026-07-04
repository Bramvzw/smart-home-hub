<?php

namespace Modules\Spotify\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Modules\Spotify\Services\SpotifyTokenService;
use Tests\TestCase;

class SpotifyTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tokenService(array $responses = []): SpotifyTokenService
    {
        $mock = new MockHandler($responses);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new SpotifyTokenService($client);
    }

    public function test_access_token_decrypts_an_encrypted_cached_value(): void
    {
        Cache::put('spotify_access_token', Crypt::encryptString('encrypted_token'), 3600);

        $service = $this->tokenService();

        $this->assertEquals('encrypted_token', $service->accessToken());
        // The cached value is untouched since it was already encrypted.
        $this->assertEquals('encrypted_token', Crypt::decryptString(Cache::get('spotify_access_token')));
    }

    public function test_access_token_migrates_legacy_plaintext_value_in_place(): void
    {
        Cache::put('spotify_access_token', 'legacy_plaintext_token', 3600);

        $service = $this->tokenService();

        // The legacy value must still be usable immediately.
        $this->assertEquals('legacy_plaintext_token', $service->accessToken());

        // And it must now be stored encrypted, not as plaintext.
        $stored = Cache::get('spotify_access_token');
        $this->assertNotEquals('legacy_plaintext_token', $stored);
        $this->assertEquals('legacy_plaintext_token', Crypt::decryptString($stored));
    }

    public function test_refresh_access_token_migrates_legacy_plaintext_refresh_token(): void
    {
        Cache::put('spotify_refresh_token', 'legacy_refresh_token', 3600);

        $service = $this->tokenService([
            new Response(200, [], json_encode([
                'access_token' => 'new_access_token',
                'expires_in' => 3600,
            ])),
        ]);

        $result = $service->refreshAccessToken();

        // The legacy refresh token was usable and the refresh call succeeded.
        $this->assertEquals('new_access_token', $result['access_token']);

        // It must now be stored encrypted (and forever, matching original storage).
        $stored = Cache::get('spotify_refresh_token');
        $this->assertNotEquals('legacy_refresh_token', $stored);
        $this->assertEquals('legacy_refresh_token', Crypt::decryptString($stored));

        // The newly issued access token is stored encrypted too.
        $this->assertEquals('new_access_token', Crypt::decryptString(Cache::get('spotify_access_token')));
    }

    public function test_access_token_returns_null_when_nothing_cached(): void
    {
        $service = $this->tokenService();

        $this->assertNull($service->accessToken());
    }
}
