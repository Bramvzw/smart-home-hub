<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Services\LightingService;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Modules\Lighting\Services\Providers\TuyaProvider;
use Tests\TestCase;

class LightControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'lighting.tuya' => ['client_id' => 'cid', 'client_secret' => 'sec', 'region' => 'eu', 'uid' => 'u1'],
            'lighting.govee' => [
                'api_key' => 'gkey',
                'model_cache_ttl' => 300,
                'control_retries' => 1,
                'command_pause_ms' => 0,
            ],
        ]);
    }

    public function test_tuya_power_sends_switch_led_command(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*/v1.0/devices/*' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->setPower('d1', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1.0/devices/d1/commands')
            && $request['commands'][0]['code'] === 'switch_led'
            && $request['commands'][0]['value'] === true);
    }

    public function test_tuya_brightness_scales_percent_to_v2(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*/v1.0/devices/*' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->setBrightness('d1', 50);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1.0/devices/d1/commands')
            && $request['commands'][0]['code'] === 'bright_value_v2'
            && $request['commands'][0]['value'] === 500);
    }

    public function test_tuya_brightness_in_colour_mode_updates_v_channel_without_switching_to_white(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*/v1.0/devices/*/status' => Http::response(['success' => true, 'result' => [
                ['code' => 'work_mode', 'value' => 'colour'],
                ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 120, 's' => 1000, 'v' => 1000])],
            ]]),
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->setBrightness('d1', 50);

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v1.0/devices/d1/commands')) {
                return false;
            }
            $codes = collect($request['commands'])->keyBy('code');

            // Brightness moves the V channel, hue/saturation are preserved, and
            // the lamp stays in colour mode instead of reverting to white.
            return $codes->has('work_mode') && $codes['work_mode']['value'] === 'colour'
                && $codes['colour_data_v2']['value'] === ['h' => 120, 's' => 1000, 'v' => 500]
                && ! $codes->has('bright_value_v2');
        });
    }

    public function test_tuya_colour_sends_hsv_object(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*/v1.0/devices/*' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->setColor('d1', '#ff0000');

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), '/v1.0/devices/d1/commands')) {
                return false;
            }
            $codes = collect($request['commands'])->keyBy('code');

            return $codes['work_mode']['value'] === 'colour'
                && $codes['colour_data_v2']['value'] === ['h' => 0, 's' => 1000, 'v' => 1000];
        });
    }

    public function test_govee_power_sends_turn_command_with_resolved_model(): void
    {
        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn']],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);

        app(GoveeProvider::class)->setPower('AA:BB', true);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'AA:BB'
            && $request['model'] === 'H6159'
            && $request['cmd']['name'] === 'turn'
            && $request['cmd']['value'] === 'on');
    }

    public function test_govee_color_sends_rgb(): void
    {
        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['color']],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);

        app(GoveeProvider::class)->setColor('AA:BB', '#ff0000');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['cmd']['name'] === 'color'
            && $request['cmd']['value'] === ['r' => 255, 'g' => 0, 'b' => 0]);
    }

    public function test_govee_control_retries_transient_failures(): void
    {
        config(['lighting.govee.control_retries' => 2]);

        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['brightness']],
            ]]]),
            '*/v1/devices/control' => Http::sequence()
                ->push(['code' => 500], 200)
                ->push(['code' => 200, 'data' => []], 200),
        ]);

        app(GoveeProvider::class)->setBrightness('AA:BB', 72);

        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'AA:BB'
            && $request['cmd']['name'] === 'brightness'
            && $request['cmd']['value'] === 72);
    }

    public function test_lighting_preset_applies_to_all_reachable_provider_lights(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'calex-1', 'name' => 'Calex lamp', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => false],
                    ['code' => 'bright_value_v2', 'value' => 100],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 0, 'v' => 1000])],
                ]],
            ]]]),
            '*/v1.0/devices/*/status' => Http::response(['success' => true, 'result' => [
                ['code' => 'work_mode', 'value' => 'white'],
            ]]),
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'govee-1', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'off'], ['brightness' => 10], ['color' => ['r' => 255, 'g' => 255, 'b' => 255]],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);

        $result = app(LightingService::class)->applyPreset('bright');

        $this->assertSame(2, $result->applied);
        $this->assertSame(0, $result->skipped);
        $this->assertSame([], $result->failedLights);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1.0/devices/calex-1/commands')
            && $request['commands'][0]['code'] === 'switch_led'
            && $request['commands'][0]['value'] === true);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1.0/devices/calex-1/commands')
            && $request['commands'][0]['code'] === 'bright_value_v2'
            && $request['commands'][0]['value'] === 1000);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'govee-1'
            && $request['cmd']['name'] === 'color'
            && $request['cmd']['value'] === ['r' => 245, 'g' => 247, 'b' => 255]);
    }

    public function test_lighting_writes_fail_fast_when_the_control_queue_is_busy(): void
    {
        config([
            'lighting.control_lock_ttl' => 10,
            'lighting.control_lock_wait' => 0,
        ]);

        $lock = Cache::lock('lighting:control', 10);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(LightingControlBusy::class);

            app(LightingService::class)->control('govee', 'AA:BB', ['power' => true]);
        } finally {
            $lock->release();
        }
    }
}
