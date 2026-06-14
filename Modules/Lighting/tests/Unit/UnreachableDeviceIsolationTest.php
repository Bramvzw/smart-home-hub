<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\LightingService;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Tests\TestCase;

class UnreachableDeviceIsolationTest extends TestCase
{
    public function test_one_failing_provider_does_not_blank_the_other(): void
    {
        config([
            'lighting.tuya' => ['client_id' => 'cid', 'client_secret' => 'sec', 'region' => 'eu', 'uid' => 'u1'],
            'lighting.govee' => ['api_key' => 'gkey'],
        ]);

        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'd1', 'name' => 'Calex lamp', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'bright_value_v2', 'value' => 300],
                ]],
            ]]]),
            '*developer-api.govee.com/v1/devices' => Http::response('upstream down', 500),
        ]);

        $snapshot = app(LightingService::class)->snapshot();

        // Tuya light still renders; Govee is reported unreachable, not fatal.
        $this->assertCount(1, $snapshot->lights);
        $this->assertSame('Calex lamp', $snapshot->lights[0]->name);
        $this->assertSame(['Govee'], $snapshot->unreachableProviders);
    }

    public function test_unreadable_device_is_listed_but_marked_unreachable(): void
    {
        config(['lighting.govee' => ['api_key' => 'gkey'], 'lighting.tuya' => ['client_id' => '', 'client_secret' => '', 'uid' => '']]);

        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'AA:BB', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn']],
            ]]]),
            '*/v1/devices/state*' => Http::response('state error', 500),
        ]);

        $lights = app(GoveeProvider::class)->lights();

        $this->assertCount(1, $lights);
        $this->assertSame('Strip', $lights[0]->name);
        $this->assertFalse($lights[0]->reachable);
    }
}
