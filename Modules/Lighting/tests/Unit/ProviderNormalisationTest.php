<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Modules\Lighting\Services\Providers\TuyaProvider;
use Tests\TestCase;

class ProviderNormalisationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'lighting.tuya' => ['client_id' => 'cid', 'client_secret' => 'sec', 'region' => 'eu', 'uid' => 'u1'],
            'lighting.govee' => ['api_key' => 'gkey'],
        ]);
    }

    public function test_tuya_device_normalises_to_shared_light(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'd1', 'name' => 'Woonkamer', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'bright_value_v2', 'value' => 500],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 120, 's' => 1000, 'v' => 1000])],
                ]],
            ]]]),
        ]);

        $lights = app(TuyaProvider::class)->lights();

        $this->assertCount(1, $lights);
        $light = $lights[0];
        $this->assertSame('tuya', $light->provider);
        $this->assertSame('Woonkamer', $light->name);
        $this->assertTrue($light->on);
        $this->assertSame(50, $light->brightness);      // 500/1000 → 50%
        $this->assertSame('#00ff00', $light->color);     // h120 s1000 v1000 → green
        $this->assertTrue($light->reachable);
        $this->assertTrue($light->supportsColor);
    }

    public function test_govee_device_normalises_to_shared_light(): void
    {
        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'retrievable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 80], ['color' => ['r' => 255, 'g' => 0, 'b' => 0]],
            ]]]),
        ]);

        $lights = app(GoveeProvider::class)->lights();

        $this->assertCount(1, $lights);
        $light = $lights[0];
        $this->assertSame('govee', $light->provider);
        $this->assertSame('Strip', $light->name);
        $this->assertTrue($light->on);
        $this->assertSame(80, $light->brightness);
        $this->assertSame('#ff0000', $light->color);
        $this->assertTrue($light->reachable);
        $this->assertTrue($light->supportsColor);
    }
}
