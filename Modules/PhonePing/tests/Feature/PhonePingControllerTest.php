<?php

namespace Modules\PhonePing\Tests\Feature;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Modules\PhonePing\Services\NtfyClient;
use Tests\TestCase;

class PhonePingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_index_shows_ping_button_when_configured(): void
    {
        $this->mockNtfy(configured: true);

        $this->get(route('phoneping.index'))
            ->assertStatus(200)
            ->assertSee('Ping');
    }

    public function test_index_shows_empty_state_when_not_configured(): void
    {
        $this->mockNtfy(configured: false);

        $this->get(route('phoneping.index'))
            ->assertStatus(200)
            ->assertSee('No phone configured');
    }

    public function test_ping_returns_200_on_success(): void
    {
        $ntfy = $this->mockNtfy(configured: true);
        $ntfy->expects($this->once())->method('send');

        $this->postJson(route('phoneping.ping'))
            ->assertStatus(200)
            ->assertJsonPath('message', 'Ping sent.');
    }

    public function test_ping_returns_502_when_ntfy_fails(): void
    {
        $ntfy = $this->mockNtfy(configured: true);
        $ntfy->method('send')->willThrowException(
            new RequestException(new Response(new GuzzleResponse(500)))
        );

        $this->postJson(route('phoneping.ping'))
            ->assertStatus(502);
    }

    public function test_ping_returns_502_when_not_configured(): void
    {
        $this->mockNtfy(configured: false);

        $this->postJson(route('phoneping.ping'))
            ->assertStatus(502);
    }

    private function mockNtfy(bool $configured): \PHPUnit\Framework\MockObject\MockObject
    {
        $ntfy = $this->createMock(NtfyClient::class);
        $ntfy->method('isConfigured')->willReturn($configured);
        $this->app->instance(NtfyClient::class, $ntfy);

        return $ntfy;
    }
}
