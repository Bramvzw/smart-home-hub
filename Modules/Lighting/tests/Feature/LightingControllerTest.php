<?php

namespace Modules\Lighting\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightingControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
        config(['lighting.tuya' => ['client_id' => '', 'client_secret' => '', 'uid' => '']]);
    }

    private function fakeGovee(string $apiKey = 'gkey'): void
    {
        config(['lighting.govee' => [
            'api_key' => $apiKey,
            'model_cache_ttl' => 300,
            'control_retries' => 1,
            'command_pause_ms' => 0,
        ]]);

        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 60], ['color' => ['r' => 0, 'g' => 0, 'b' => 255]],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);
    }

    public function test_index_lists_lights_with_controls(): void
    {
        $this->fakeGovee();

        $response = $this->get(route('lighting.index'));

        $response->assertStatus(200);
        $response->assertSee('Strip');
        $response->assertSee('Brightness');
        $response->assertSee('Presets');
        $response->assertSee('Cozy');
        $response->assertSee('data-preset-url-template', false);
    }

    public function test_index_shows_no_credentials_state(): void
    {
        config(['lighting.govee' => ['api_key' => '']]);

        $response = $this->get(route('lighting.index'));

        $response->assertStatus(200);
        $response->assertSee('No lights connected');
    }

    public function test_page_does_not_leak_the_api_key(): void
    {
        $this->fakeGovee('GOVEE-SECRET-XYZ');

        $response = $this->get(route('lighting.index'));

        $response->assertStatus(200);
        $response->assertSee('Strip');
        $response->assertDontSee('GOVEE-SECRET-XYZ');
    }

    public function test_control_endpoint_updates_a_light(): void
    {
        $this->fakeGovee();

        $response = $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), [
            'power' => true,
            'brightness' => 60,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', 'DEV1');
        $response->assertJsonPath('data.provider', 'govee');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control'));
    }

    public function test_preset_endpoint_applies_a_preset_to_govee_lights(): void
    {
        $this->fakeGovee();

        $response = $this->postJson(route('lighting.presets.apply', ['preset' => 'cozy']));

        $response->assertStatus(200);
        $response->assertJsonPath('data.preset.key', 'cozy');
        $response->assertJsonPath('data.applied', 1);
        $response->assertJsonPath('data.skipped', 0);

        Http::assertSentCount(4);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'turn'
            && $request['cmd']['value'] === 'on');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'brightness'
            && $request['cmd']['value'] === 72);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'color'
            && $request['cmd']['value'] === ['r' => 255, 'g' => 194, 'b' => 107]);
    }

    public function test_unknown_preset_returns_not_found(): void
    {
        $this->fakeGovee();

        $this->postJson(route('lighting.presets.apply', ['preset' => 'missing']))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Onbekende preset.');
    }

    public function test_busy_lighting_queue_returns_conflict(): void
    {
        config([
            'lighting.control_lock_ttl' => 10,
            'lighting.control_lock_wait' => 0,
        ]);

        $lock = Cache::lock('lighting:control', 10);
        $this->assertTrue($lock->get());

        try {
            $this->postJson(route('lighting.presets.apply', ['preset' => 'cozy']))
                ->assertStatus(409)
                ->assertJsonPath('message', 'Er loopt al een lampactie. Probeer het zo opnieuw.');
        } finally {
            $lock->release();
        }
    }
}
