<?php

namespace Modules\Spotify\Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Spotify\Services\SpotifyApiClient;
use Modules\Spotify\Services\SpotifyTokenService;
use Tests\TestCase;

class SpotifyApiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_request_returns_error_when_connection_times_out()
    {
        Cache::put('spotify_access_token', 'test_token', 3600);

        $mock = new MockHandler([
            new ConnectException('Connection timed out', new Request('GET', 'https://api.spotify.com/v1/me')),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $api = new SpotifyApiClient($client, new SpotifyTokenService($client));

        $result = $api->request('GET', '/me');

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Spotify API request failed', $result['error']);
    }

    public function test_service_provider_binds_client_with_configured_timeout()
    {
        config(['spotify.request_timeout' => 7]);

        $client = $this->app->make(ClientInterface::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals(7.0, $client->getConfig('timeout'));
        $this->assertEquals(7.0, $client->getConfig('connect_timeout'));
    }

    public function test_service_provider_binds_client_with_default_timeout()
    {
        $client = $this->app->make(ClientInterface::class);

        $this->assertEquals(10.0, $client->getConfig('timeout'));
        $this->assertEquals(10.0, $client->getConfig('connect_timeout'));
    }
}
