<?php

namespace Modules\Lighting\Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightGroupControlTest extends TestCase
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

    /**
     * @param  array<string, mixed>  $overrides  extra Http::fake patterns, matched first
     */
    private function fakeProviders(array $overrides = []): void
    {
        $tuyaLamp = static fn (string $id, string $name, bool $online = true): array => [
            'id' => $id, 'name' => $name, 'online' => $online, 'status' => [
                ['code' => 'switch_led', 'value' => true],
                ['code' => 'work_mode', 'value' => 'colour'],
                ['code' => 'bright_value_v2', 'value' => 1000],
                ['code' => 'temp_value_v2', 'value' => 500],
                ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 1000, 'v' => 800])],
            ],
        ];

        Http::fake($overrides + [
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                $tuyaLamp('t1', 'Lamp 1'),
                $tuyaLamp('t2', 'Lamp 2'),
                $tuyaLamp('t3', 'Lamp 3', false),
            ]]]),
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color', 'colorTem']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 60], ['color' => ['r' => 0, 'g' => 0, 'b' => 255]],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);
    }

    /**
     * @param  list<array{provider: string, id: string}>  $lights
     * @param  array<string, mixed>  $changes
     */
    private function group(array $lights, array $changes): \Illuminate\Testing\TestResponse
    {
        return $this->putJson(route('lighting.lights.update-many'), ['lights' => $lights, 'changes' => $changes]);
    }

    public function test_group_write_applies_one_change_set_to_every_light(): void
    {
        $this->fakeProviders();

        $response = $this->group([
            ['provider' => 'tuya', 'id' => 't1'],
            ['provider' => 'tuya', 'id' => 't2'],
            ['provider' => 'govee', 'id' => 'DEV1'],
        ], ['power' => true, 'brightness' => 60, 'color_temp' => 20]);

        $response->assertStatus(200);
        $response->assertExactJson(['data' => ['applied' => 3, 'failed' => []]]);

        foreach (['t1', 't2'] as $id) {
            Http::assertSent(fn (Request $request): bool => str_contains($request->url(), "/v1.0/devices/{$id}/commands")
                && $request['commands'] === [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'white'],
                    ['code' => 'temp_value_v2', 'value' => 200],
                    ['code' => 'bright_value_v2', 'value' => 600],
                ]);
        }

        // Group writes build commands from the cached snapshot: no status reads.
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/status'));

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1/devices/control')
            && $request['cmd']['name'] === 'colorTem'
            && $request['cmd']['value'] === 3400);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1/devices/control')
            && $request['cmd']['name'] === 'brightness'
            && $request['cmd']['value'] === 60);
    }

    public function test_group_brightness_keeps_colour_mode_lamps_in_colour(): void
    {
        $this->fakeProviders();

        $this->group([['provider' => 'tuya', 'id' => 't1']], ['brightness' => 30])
            ->assertStatus(200)
            ->assertJsonPath('data.applied', 1);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/t1/commands')
            && $request['commands'] === [
                ['code' => 'switch_led', 'value' => true],
                ['code' => 'work_mode', 'value' => 'colour'],
                ['code' => 'colour_data_v2', 'value' => ['h' => 0, 's' => 1000, 'v' => 300]],
            ]);
    }

    public function test_partial_failure_returns_200_with_the_failed_light_names(): void
    {
        $this->fakeProviders([
            '*/v1.0/devices/t2/commands' => Http::response(['success' => false, 'code' => 2001]),
        ]);

        $response = $this->group([
            ['provider' => 'tuya', 'id' => 't1'],
            ['provider' => 'tuya', 'id' => 't2'],
            ['provider' => 'tuya', 'id' => 't3'],
        ], ['power' => false]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', 1);
        $response->assertJsonPath('data.failed', ['Lamp 3', 'Lamp 2']);
    }

    public function test_full_failure_returns_502(): void
    {
        $this->fakeProviders([
            '*/v1.0/devices/*/commands' => Http::response(['success' => false, 'code' => 2001]),
        ]);

        $this->group([
            ['provider' => 'tuya', 'id' => 't1'],
            ['provider' => 'tuya', 'id' => 't2'],
        ], ['power' => true])
            ->assertStatus(502)
            ->assertJsonPath('data.applied', 0)
            ->assertJsonPath('data.failed', ['Lamp 1', 'Lamp 2']);
    }

    public function test_unknown_light_returns_422_without_writing_anything(): void
    {
        $this->fakeProviders();

        $this->group([
            ['provider' => 'tuya', 'id' => 't1'],
            ['provider' => 'tuya', 'id' => 'missing'],
        ], ['power' => true])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Unknown light.');

        $this->group([['provider' => 'hue', 'id' => 'x']], ['power' => true])
            ->assertStatus(422);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/commands')
            || str_contains($request->url(), '/v1/devices/control'));
    }

    public function test_group_validation_rejects_bad_payloads(): void
    {
        $this->fakeProviders();

        $this->group([['provider' => 'tuya', 'id' => 't1']], ['color' => '#ff0000', 'color_temp' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('changes.color_temp');

        $this->group([['provider' => 'tuya', 'id' => 't1']], [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('changes');

        $this->group([], ['power' => true])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lights');
    }

    public function test_group_write_returns_409_while_the_group_lock_is_held(): void
    {
        $this->fakeProviders();
        config([
            'lighting.control_lock_ttl' => 10,
            'lighting.control_lock_wait' => 0,
        ]);

        $lock = Cache::lock('lighting:control', 10);
        $this->assertTrue($lock->get());

        try {
            $this->group([['provider' => 'tuya', 'id' => 't1']], ['power' => true])->assertStatus(409);
        } finally {
            $lock->release();
        }
    }
}
