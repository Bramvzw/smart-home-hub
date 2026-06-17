<?php

namespace Modules\PhonePing\Tests\Unit;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Modules\PhonePing\Actions\PingPhone;
use Modules\PhonePing\Services\NtfyClient;
use PHPUnit\Framework\TestCase;

class PingPhoneTest extends TestCase
{
    public function test_returns_success_when_ntfy_responds(): void
    {
        $ntfy = $this->createMock(NtfyClient::class);
        $ntfy->method('isConfigured')->willReturn(true);
        $ntfy->expects($this->once())->method('send');

        $result = (new PingPhone($ntfy))();

        $this->assertTrue($result->success);
    }

    public function test_returns_failure_when_not_configured(): void
    {
        $ntfy = $this->createMock(NtfyClient::class);
        $ntfy->method('isConfigured')->willReturn(false);
        $ntfy->expects($this->never())->method('send');

        $result = (new PingPhone($ntfy))();

        $this->assertFalse($result->success);
    }

    public function test_returns_failure_when_ntfy_throws(): void
    {
        $ntfy = $this->createMock(NtfyClient::class);
        $ntfy->method('isConfigured')->willReturn(true);
        $ntfy->method('send')->willThrowException(new \RuntimeException('timeout'));

        $result = (new PingPhone($ntfy))();

        $this->assertFalse($result->success);
    }

    public function test_returns_failure_with_http_status_on_request_exception(): void
    {
        $ntfy = $this->createMock(NtfyClient::class);
        $ntfy->method('isConfigured')->willReturn(true);
        $ntfy->method('send')->willThrowException(
            new RequestException(new Response(new GuzzleResponse(429)))
        );

        $result = (new PingPhone($ntfy))();

        $this->assertFalse($result->success);
        $this->assertStringContainsString('429', $result->message);
    }
}
