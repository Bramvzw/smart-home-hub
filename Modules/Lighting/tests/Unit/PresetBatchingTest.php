<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Services\Providers\TuyaProvider;
use Tests\TestCase;

class PresetBatchingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('lighting:tuya:token');
        config(['lighting.tuya' => ['client_id' => 'id', 'client_secret' => 'secret', 'region' => 'eu', 'uid' => 'u1']]);
    }

    public function test_a_full_target_state_is_one_batched_command_without_status_reads(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 't', 'expire_time' => 7200]]),
            '*/v1.0/devices/d1/commands' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->applyState('d1', true, 40, '#ff0000');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/v1.0/devices/d1/commands')) {
                return false;
            }
            $codes = collect((array) $request['commands'])->keyBy('code');

            return $codes->has('switch_led')
                && $codes->has('work_mode')
                && (int) $codes->get('colour_data_v2')['value']['v'] === 400;
        });

        // The whole point: no per-lamp status read before the write.
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/status'));
    }

    public function test_power_off_presets_send_a_single_switch_command(): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 't', 'expire_time' => 7200]]),
            '*/v1.0/devices/d1/commands' => Http::response(['success' => true, 'result' => true]),
        ]);

        app(TuyaProvider::class)->applyState('d1', false, 40, '#ff0000');

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/v1.0/devices/d1/commands')) {
                return false;
            }
            $codes = collect((array) $request['commands'])->keyBy('code');

            return $codes->get('switch_led')['value'] === false && $codes->count() === 1;
        });
    }
}
