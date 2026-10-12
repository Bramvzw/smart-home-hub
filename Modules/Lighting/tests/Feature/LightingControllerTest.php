<?php

namespace Modules\Lighting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightingControllerTest extends TestCase
{
    use RefreshDatabase;

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
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color', 'colorTem']],
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
        $response->assertSeeHtml('aria-label="Scenes"');
        $response->assertSee('Cozy');
        $response->assertSee('Night light');
        $response->assertSeeHtml('data-preset-url-template');
        $response->assertSeeHtml('data-state-url="'.route('lighting.state').'"');
        $response->assertSeeHtml('data-poll-seconds="120"');
    }

    public function test_state_endpoint_returns_lights_and_unreachable_providers(): void
    {
        $this->fakeGovee();

        $response = $this->getJson(route('lighting.state'));

        $response->assertStatus(200);
        $response->assertExactJson([
            'data' => [[
                'provider' => 'govee',
                'id' => 'DEV1',
                'name' => 'Strip',
                'on' => true,
                'brightness' => 60,
                'color' => '#0000ff',
                'reachable' => true,
                'supports_color' => true,
                'mode' => 'colour',
                'color_temp' => null,
                'supports_color_temp' => true,
            ]],
            'unreachable_providers' => [],
        ]);
    }

    public function test_state_endpoint_reports_unreachable_providers(): void
    {
        config(['lighting.govee' => ['api_key' => 'gkey']]);
        Http::fake(['*' => Http::response(['code' => 500], 500)]);

        $this->getJson(route('lighting.state'))
            ->assertStatus(200)
            ->assertJsonPath('data', [])
            ->assertJsonPath('unreachable_providers', ['Govee']);
    }

    public function test_single_light_writes_only_lock_their_own_light(): void
    {
        $this->fakeGovee();
        config([
            'lighting.control_lock_ttl' => 10,
            'lighting.control_lock_wait' => 0,
        ]);

        $other = Cache::lock('lighting:control:govee:OTHER', 10);
        $this->assertTrue($other->get());

        try {
            $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), ['power' => true])
                ->assertStatus(200);
        } finally {
            $other->release();
        }

        $own = Cache::lock('lighting:control:govee:DEV1', 10);
        $this->assertTrue($own->get());

        try {
            $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), ['power' => true])
                ->assertStatus(409);
        } finally {
            $own->release();
        }
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

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control'));
    }

    public function test_preset_endpoint_applies_a_preset_to_govee_lights(): void
    {
        $this->fakeGovee();

        $response = $this->postJson(route('lighting.presets.apply', ['preset' => 'cozy']));

        $response->assertStatus(200);
        $response->assertJsonPath('data.preset.key', 'cozy');
        $response->assertJsonPath('data.applied', 1);
        $response->assertJsonPath('data.skipped', 0);

        Http::assertSentCount(5);

        // A preset deterministically overwrites the lamp's state, so the power
        // command is sent even when the lamp is already on.
        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'turn'
            && $request['cmd']['value'] === 'on');

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'brightness'
            && $request['cmd']['value'] === 70);

        // Cozy is warm white: the warmest colour temperature, no RGB colour.
        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'DEV1'
            && $request['cmd']['name'] === 'colorTem'
            && $request['cmd']['value'] === 2000);
        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['cmd']['name'] === 'color');
    }

    public function test_control_endpoint_sets_a_colour_temperature(): void
    {
        $this->fakeGovee();

        $response = $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), [
            'color_temp' => 50,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.mode', 'white');
        $response->assertJsonPath('data.color_temp', 50);
        $response->assertJsonPath('data.supports_color_temp', true);
        // The response is the cached lamp with the change applied, not a guess.
        $response->assertJsonPath('data.supports_color', true);
        $response->assertJsonPath('data.name', 'Strip');
        $response->assertJsonPath('data.brightness', 60);

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['cmd']['name'] === 'colorTem'
            && $request['cmd']['value'] === 5500);
    }

    public function test_control_endpoint_rejects_colour_and_colour_temperature_together(): void
    {
        $this->fakeGovee();

        $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), [
            'color' => '#ff0000',
            'color_temp' => 50,
        ])->assertStatus(422)->assertJsonValidationErrors('color_temp');

        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control'));
    }

    public function test_control_endpoint_rejects_an_out_of_range_colour_temperature(): void
    {
        $this->fakeGovee();

        $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'DEV1']), [
            'color_temp' => 101,
        ])->assertStatus(422)->assertJsonValidationErrors('color_temp');
    }

    public function test_night_light_preset_only_targets_the_led_strip(): void
    {
        config(['lighting.govee' => [
            'api_key' => 'gkey',
            'model_cache_ttl' => 300,
            'control_retries' => 1,
            'command_pause_ms' => 0,
        ]]);

        Http::fake([
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'STRIP1', 'model' => 'H6159', 'deviceName' => 'LED Strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
                ['device' => 'LAMP1', 'model' => 'H6008', 'deviceName' => 'Desk lamp', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'off'], ['brightness' => 45], ['color' => ['r' => 255, 'g' => 255, 'b' => 255]],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);

        $response = $this->postJson(route('lighting.presets.apply', ['preset' => 'night_light']));

        $response->assertStatus(200);
        $response->assertJsonPath('data.preset.key', 'night_light');
        $response->assertJsonPath('data.preset.target_name_contains.0', 'strip');
        $response->assertJsonPath('data.applied', 1);
        $response->assertJsonPath('data.skipped', 1);

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'STRIP1'
            && $request['cmd']['name'] === 'turn'
            && $request['cmd']['value'] === 'on');

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'STRIP1'
            && $request['cmd']['name'] === 'brightness'
            && $request['cmd']['value'] === 1);

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'STRIP1'
            && $request['cmd']['name'] === 'color'
            && $request['cmd']['value'] === ['r' => 255, 'g' => 133, 'b' => 89]);

        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control')
            && $request['device'] === 'LAMP1');
    }

    public function test_unknown_preset_returns_not_found(): void
    {
        $this->fakeGovee();

        $this->postJson(route('lighting.presets.apply', ['preset' => 'missing']))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Unknown preset.');
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
                ->assertJsonPath('message', 'A lighting action is already running. Try again in a moment.');
        } finally {
            $lock->release();
        }
    }

    public function test_single_light_write_to_an_unknown_light_or_provider_returns_422(): void
    {
        $this->fakeGovee();

        $this->putJson(route('lighting.lights.update', ['provider' => 'hue', 'id' => 'x']), ['power' => true])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Unknown light.');
        $this->putJson(route('lighting.lights.update', ['provider' => 'govee', 'id' => 'MISSING']), ['power' => true])
            ->assertStatus(422);

        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/v1/devices/control'));
    }
}
