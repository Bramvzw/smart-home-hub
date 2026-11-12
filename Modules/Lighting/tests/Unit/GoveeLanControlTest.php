<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\Providers\GoveeLanClient;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Tests\TestCase;

class GoveeLanControlTest extends TestCase
{
    private RecordingGoveeLanClient $lan;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'lighting.govee' => [
                'api_key' => 'gkey',
                'model_cache_ttl' => 300,
                'control_retries' => 1,
                'command_pause_ms' => 0,
                'lan_devices' => 'AA:BB:CC@192.168.1.50, broken-entry, DD:EE@not-an-ip',
                'lan_command_gap_ms' => 0,
            ],
        ]);

        $this->lan = new RecordingGoveeLanClient;
        $this->app->instance(GoveeLanClient::class, $this->lan);

        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                [
                    'device' => 'AA:BB:CC', 'model' => 'H61E0', 'deviceName' => 'Strip', 'controllable' => true,
                    'supportCmds' => ['turn', 'brightness', 'color', 'colorTem'],
                    'properties' => ['colorTem' => ['range' => ['min' => 2000, 'max' => 9000]]],
                ],
                ['device' => 'DD:EE', 'model' => 'H6159', 'deviceName' => 'Cloud strip', 'controllable' => true, 'supportCmds' => ['turn']],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);
    }

    public function test_writes_to_a_lan_device_go_over_udp_not_the_cloud(): void
    {
        $provider = app(GoveeProvider::class);

        $provider->setPower('AA:BB:CC', true);
        $provider->setBrightness('AA:BB:CC', 0);
        $provider->setColor('AA:BB:CC', '#ff8000');
        $provider->setColorTemp('AA:BB:CC', 50);

        $this->assertSame([
            ['192.168.1.50', ['cmd' => 'turn', 'data' => ['value' => 1]]],
            // 0 is invalid on the LAN API; the lowest level is 1.
            ['192.168.1.50', ['cmd' => 'brightness', 'data' => ['value' => 1]]],
            ['192.168.1.50', ['cmd' => 'colorwc', 'data' => ['color' => ['r' => 255, 'g' => 128, 'b' => 0], 'colorTemInKelvin' => 0]]],
            ['192.168.1.50', ['cmd' => 'colorwc', 'data' => ['color' => ['r' => 0, 'g' => 0, 'b' => 0], 'colorTemInKelvin' => 5500]]],
        ], $this->lan->sent);
        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control'));
    }

    public function test_devices_without_a_valid_lan_address_still_use_the_cloud(): void
    {
        app(GoveeProvider::class)->setPower('DD:EE', false);

        $this->assertSame([], $this->lan->sent);
        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'DD:EE'
            && $request['cmd'] === ['name' => 'turn', 'value' => 'off']);
    }

    public function test_preset_sequence_for_a_lan_device_is_sent_in_order(): void
    {
        // The "Night" preset: on, warmest white, 1 %.
        app(GoveeProvider::class)->applyState('AA:BB:CC', true, 1, null, 0);

        $this->assertSame(['turn', 'colorwc', 'brightness'], array_map(
            static fn (array $entry): string => $entry[1]['cmd'],
            $this->lan->sent,
        ));
        $this->assertSame(2000, $this->lan->sent[1][1]['data']['colorTemInKelvin']);
    }
}

class RecordingGoveeLanClient extends GoveeLanClient
{
    /** @var list<array{0: string, 1: array<string, mixed>}> */
    public array $sent = [];

    protected function transmit(string $ip, string $payload): void
    {
        $this->sent[] = [$ip, json_decode($payload, true, 512, JSON_THROW_ON_ERROR)['msg']];
    }
}
