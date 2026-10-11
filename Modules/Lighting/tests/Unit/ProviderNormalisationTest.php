<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Lighting\Enums\LightMode;
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

    public function test_tuya_white_mode_reads_white_brightness_and_colour_temperature(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'd1', 'name' => 'Spot', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'white'],
                    ['code' => 'bright_value_v2', 'value' => 10],
                    ['code' => 'temp_value_v2', 'value' => 250],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 120, 's' => 1000, 'v' => 900])],
                ]],
            ]]]),
        ]);

        $light = app(TuyaProvider::class)->lights()[0];

        $this->assertSame(LightMode::White, $light->mode);
        $this->assertSame(1, $light->brightness);       // bright_value_v2 10 → 1%
        $this->assertSame(25, $light->colorTemp);       // temp_value_v2 250 → 25
        $this->assertTrue($light->supportsColorTemp);
        $this->assertSame('white', $light->toArray()['mode']);
        $this->assertSame(25, $light->toArray()['color_temp']);
        $this->assertTrue($light->toArray()['supports_color_temp']);
    }

    public function test_tuya_colour_mode_reads_brightness_from_the_v_channel(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'd1', 'name' => 'Spot', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'colour'],
                    ['code' => 'bright_value_v2', 'value' => 1000],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 1000, 'v' => 420])],
                ]],
            ]]]),
        ]);

        $light = app(TuyaProvider::class)->lights()[0];

        $this->assertSame(LightMode::Colour, $light->mode);
        $this->assertSame(42, $light->brightness);
        $this->assertSame('#ff0000', $light->color);
        $this->assertNull($light->colorTemp);
        $this->assertFalse($light->supportsColorTemp);
    }

    public function test_govee_colour_temperature_support_and_kelvin_mapping(): void
    {
        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6008', 'deviceName' => 'Bulb', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color', 'colorTem'],
                    'properties' => ['colorTem' => ['range' => ['min' => 2000, 'max' => 6000]]]],
                ['device' => 'CC:DD', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 50], ['colorTemInKelvin' => 3000],
            ]]]),
        ]);

        [$bulb, $strip] = app(GoveeProvider::class)->lights();

        $this->assertTrue($bulb->supportsColorTemp);
        $this->assertSame(LightMode::White, $bulb->mode);
        $this->assertSame(25, $bulb->colorTemp);        // 3000 K in 2000-6000 K → 25
        $this->assertFalse($strip->supportsColorTemp);  // no colorTem in supportCmds
        $this->assertNull($strip->colorTemp);
    }
}
