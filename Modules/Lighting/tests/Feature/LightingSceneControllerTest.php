<?php

namespace Modules\Lighting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Modules\Lighting\Models\LightingScene;
use Modules\Lighting\Models\LightingSceneLight;
use Tests\TestCase;

class LightingSceneControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->withoutVite();
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
     * Tuya: t1 warm white 70 %, t2 red colour 40 %, t3 off, t4 offline.
     * Govee: the LED strip in blue at 60 %.
     *
     * @param  array<string, mixed>  $overrides  extra Http::fake patterns, matched first
     */
    private function fakeProviders(array $overrides = []): void
    {
        Http::fake($overrides + [
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 't1', 'name' => 'Spot 1', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'white'],
                    ['code' => 'bright_value_v2', 'value' => 700],
                    ['code' => 'temp_value_v2', 'value' => 100],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 1000, 'v' => 1000])],
                ]],
                ['id' => 't2', 'name' => 'Spot 2', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'colour'],
                    ['code' => 'bright_value_v2', 'value' => 1000],
                    ['code' => 'temp_value_v2', 'value' => 500],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 1000, 'v' => 400])],
                ]],
                ['id' => 't3', 'name' => 'Spot 3', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => false],
                    ['code' => 'work_mode', 'value' => 'white'],
                    ['code' => 'bright_value_v2', 'value' => 500],
                    ['code' => 'temp_value_v2', 'value' => 500],
                ]],
                ['id' => 't4', 'name' => 'Hallway', 'online' => false, 'status' => [
                    ['code' => 'switch_led', 'value' => false],
                    ['code' => 'bright_value_v2', 'value' => 500],
                ]],
            ]]]),
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'LED strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 60], ['color' => ['r' => 0, 'g' => 0, 'b' => 255]],
            ]]]),
            '*/v1/devices/control' => Http::response(['code' => 200, 'data' => []]),
        ]);
    }

    /**
     * @param  list<array<string, string>>  $lights  may be malformed on purpose (validation tests)
     */
    private function storeScene(string $name, array $lights, string $accent = '#4EB0E6'): TestResponse
    {
        return $this->postJson(route('lighting.scenes.store'), ['name' => $name, 'accent' => $accent, 'lights' => $lights]);
    }

    /**
     * @param  list<array<string, mixed>>  $lights
     */
    private function makeScene(string $name, array $lights, int $sortOrder = 1): LightingScene
    {
        $scene = LightingScene::query()->create(['name' => $name, 'accent' => '#e0a44e', 'sort_order' => $sortOrder]);
        foreach ($lights as $light) {
            $scene->lights()->create($light + ['brightness' => null, 'color' => null, 'color_temp' => null]);
        }

        return $scene;
    }

    public function test_store_captures_the_current_state_of_the_given_lamps(): void
    {
        $this->fakeProviders();

        $response = $this->storeScene('Reading', [
            ['provider' => 'tuya', 'id' => 't1'],
            ['provider' => 'tuya', 'id' => 't2'],
            ['provider' => 'tuya', 'id' => 't3'],
            ['provider' => 'tuya', 'id' => 't4'],
            ['provider' => 'govee', 'id' => 'DEV1'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Reading');
        $response->assertJsonPath('data.accent', '#4eb0e6');
        $response->assertJsonPath('data.sort_order', 1);
        // The offline lamp is skipped: its state is unknown.
        $response->assertJsonPath('data.lights', [
            ['provider' => 'tuya', 'id' => 't1', 'power' => true, 'brightness' => 70, 'mode' => 'white', 'color' => null, 'color_temp' => 10],
            ['provider' => 'tuya', 'id' => 't2', 'power' => true, 'brightness' => 40, 'mode' => 'colour', 'color' => '#ff0000', 'color_temp' => null],
            ['provider' => 'tuya', 'id' => 't3', 'power' => false, 'brightness' => null, 'mode' => 'white', 'color' => null, 'color_temp' => null],
            ['provider' => 'govee', 'id' => 'DEV1', 'power' => true, 'brightness' => 60, 'mode' => 'colour', 'color' => '#0000ff', 'color_temp' => null],
        ]);

        $this->assertDatabaseCount('lighting_scene_lights', 4);
        $this->assertDatabaseHas('lighting_scene_lights', ['light_id' => 't1', 'mode' => 'white', 'color_temp' => 10, 'brightness' => 70]);

        $this->storeScene('Second', [['provider' => 'tuya', 'id' => 't1']])->assertJsonPath('data.sort_order', 2);
    }

    public function test_store_validates_the_payload(): void
    {
        $this->fakeProviders();

        $this->postJson(route('lighting.scenes.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'accent', 'lights']);

        $this->storeScene(str_repeat('x', 61), [['provider' => 'tuya', 'id' => 't1']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->storeScene('Ok', [['provider' => 'tuya', 'id' => 't1']], 'orange')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['accent']);

        $this->storeScene('Ok', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lights']);

        $this->storeScene('Ok', [['provider' => 'tuya']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lights.0.id']);

        $this->assertDatabaseCount('lighting_scenes', 0);
    }

    public function test_store_rejects_unknown_or_only_unreachable_lamps(): void
    {
        $this->fakeProviders();

        $this->storeScene('Ghost', [['provider' => 'tuya', 'id' => 'nope']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lights']);

        $this->storeScene('Offline', [['provider' => 'tuya', 'id' => 't4']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lights']);

        $this->assertDatabaseCount('lighting_scenes', 0);
    }

    public function test_index_lists_built_in_presets_then_user_scenes_in_order(): void
    {
        $this->makeScene('Gaming', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']], 2);
        $this->makeScene('Dinner', [['provider' => 'tuya', 'light_id' => 't1', 'power' => true, 'brightness' => 45, 'mode' => 'white', 'color_temp' => 8]], 1);

        $response = $this->getJson(route('lighting.scenes.index'));

        $response->assertStatus(200);
        $response->assertJsonPath('data.presets.0.key', 'off');
        $response->assertJsonPath('data.presets.1.key', 'bright');
        $response->assertJsonPath('data.scenes.0.name', 'Dinner');
        $response->assertJsonPath('data.scenes.0.lights.0', ['provider' => 'tuya', 'id' => 't1', 'power' => true, 'brightness' => 45, 'mode' => 'white', 'color' => null, 'color_temp' => 8]);
        $response->assertJsonPath('data.scenes.1.name', 'Gaming');
    }

    public function test_update_renames_and_recolours_without_touching_stored_states(): void
    {
        $this->fakeProviders();
        $scene = $this->makeScene('Dinner', [['provider' => 'tuya', 'light_id' => 't1', 'power' => true, 'brightness' => 45, 'mode' => 'white', 'color_temp' => 8]]);

        $this->putJson(route('lighting.scenes.update', $scene), ['name' => 'Late dinner', 'accent' => '#B06BFF'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Late dinner')
            ->assertJsonPath('data.accent', '#b06bff')
            ->assertJsonPath('data.lights.0.brightness', 45);

        Http::assertNothingSent();
    }

    public function test_update_with_lights_recaptures_the_current_state(): void
    {
        $this->fakeProviders();
        $scene = $this->makeScene('Dinner', [['provider' => 'tuya', 'light_id' => 't1', 'power' => true, 'brightness' => 45, 'mode' => 'white', 'color_temp' => 8]]);

        $this->putJson(route('lighting.scenes.update', $scene), ['lights' => [['provider' => 'tuya', 'id' => 't1'], ['provider' => 'tuya', 'id' => 't3']]])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Dinner')
            ->assertJsonCount(2, 'data.lights')
            ->assertJsonPath('data.lights.0.brightness', 70)
            ->assertJsonPath('data.lights.0.color_temp', 10)
            ->assertJsonPath('data.lights.1.power', false);

        $this->assertSame(2, LightingSceneLight::query()->where('lighting_scene_id', $scene->id)->count());
    }

    public function test_update_to_current_keeps_the_stored_state_of_an_offline_lamp(): void
    {
        $this->fakeProviders();
        $scene = $this->makeScene('Dinner', [
            ['provider' => 'tuya', 'light_id' => 't1', 'power' => true, 'brightness' => 45, 'mode' => 'white', 'color_temp' => 8],
            ['provider' => 'tuya', 'light_id' => 't4', 'power' => true, 'brightness' => 20, 'mode' => 'colour', 'color' => '#9900ff'],
        ]);

        $this->putJson(route('lighting.scenes.update', $scene), ['lights' => [['provider' => 'tuya', 'id' => 't1'], ['provider' => 'tuya', 'id' => 't4']]])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.lights')
            ->assertJsonPath('data.lights.0.brightness', 70)
            ->assertJsonPath('data.lights.1', ['provider' => 'tuya', 'id' => 't4', 'power' => true, 'brightness' => 20, 'mode' => 'colour', 'color' => '#9900ff', 'color_temp' => null]);
    }

    public function test_update_to_current_with_only_offline_lamps_is_rejected_and_keeps_the_scene(): void
    {
        $this->fakeProviders();
        $scene = $this->makeScene('Hall', [['provider' => 'tuya', 'light_id' => 't4', 'power' => true, 'brightness' => 20, 'mode' => 'white']]);

        $this->putJson(route('lighting.scenes.update', $scene), ['lights' => [['provider' => 'tuya', 'id' => 't4']]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('lights');

        $this->assertSame(1, LightingSceneLight::query()->where('lighting_scene_id', $scene->id)->count());
    }

    public function test_unknown_scene_ids_are_404(): void
    {
        $this->fakeProviders();

        $this->putJson(route('lighting.scenes.update', ['scene' => 999]), ['name' => 'Ghost'])->assertNotFound();
        $this->postJson(route('lighting.scenes.apply', ['scene' => 999]))->assertNotFound();
        $this->deleteJson(route('lighting.scenes.destroy', ['scene' => 999]))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_update_validates_the_payload(): void
    {
        $scene = $this->makeScene('Dinner', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']]);

        $this->putJson(route('lighting.scenes.update', $scene), ['name' => '', 'accent' => '#12345', 'lights' => []])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'accent', 'lights']);
    }

    public function test_destroy_deletes_the_scene_and_its_lamp_states(): void
    {
        $scene = $this->makeScene('Dinner', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']]);

        $this->deleteJson(route('lighting.scenes.destroy', $scene))->assertNoContent();

        $this->assertDatabaseCount('lighting_scenes', 0);
        $this->assertDatabaseCount('lighting_scene_lights', 0);
        $this->deleteJson(route('lighting.scenes.destroy', $scene))->assertNotFound();
    }

    public function test_apply_changes_only_stored_lamps_and_reports_unreachable_ones(): void
    {
        $this->fakeProviders();
        $scene = $this->makeScene('Gaming', [
            ['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white'],
            ['provider' => 'tuya', 'light_id' => 't2', 'power' => true, 'brightness' => 30, 'mode' => 'colour', 'color' => '#9900ff'],
            ['provider' => 'tuya', 'light_id' => 't4', 'power' => true, 'brightness' => 50, 'mode' => 'white', 'color_temp' => 0],
            ['provider' => 'tuya', 'light_id' => 'gone', 'power' => true, 'brightness' => 50, 'mode' => 'white'],
            ['provider' => 'govee', 'light_id' => 'DEV1', 'power' => true, 'brightness' => 70, 'mode' => 'colour', 'color' => '#9900ff'],
        ]);

        $response = $this->postJson(route('lighting.scenes.apply', $scene));

        $response->assertStatus(200);
        $response->assertExactJson(['data' => ['applied' => 3, 'failed' => ['Hallway'], 'skipped' => [], 'missing_lights' => [['provider' => 'tuya', 'id' => 'gone']]]]);

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/t1/commands')
            && $request['commands'] === [['code' => 'switch_led', 'value' => false]]);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/t2/commands')
            && in_array(['code' => 'work_mode', 'value' => 'colour'], $request['commands'], true));
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1/devices/control')
            && ($request['cmd']['name'] ?? null) === 'color');
        // Spot 3 is not in the scene and the offline lamp is skipped.
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/t3/commands'));
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/t4/commands'));
    }

    public function test_apply_returns_502_when_nothing_could_be_applied(): void
    {
        $this->fakeProviders(['*/v1.0/devices/*/commands' => Http::response(['success' => false, 'code' => 2001])]);
        $scene = $this->makeScene('Off', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']]);

        $this->postJson(route('lighting.scenes.apply', $scene))
            ->assertStatus(502)
            ->assertJsonPath('data.failed', ['Spot 1']);
    }

    public function test_apply_reports_lamps_that_are_gone_or_unconfigured(): void
    {
        $this->fakeProviders();
        config(['lighting.govee.api_key' => '']);
        $scene = $this->makeScene('Old', [
            ['provider' => 'tuya', 'light_id' => 'gone', 'power' => false, 'mode' => 'white'],
            ['provider' => 'govee', 'light_id' => 'DEV1', 'power' => false, 'mode' => 'white'],
        ]);

        $this->postJson(route('lighting.scenes.apply', $scene))
            ->assertStatus(200)
            ->assertJsonPath('data.applied', 0)
            ->assertJsonPath('data.missing_lights', [['provider' => 'tuya', 'id' => 'gone'], ['provider' => 'govee', 'id' => 'DEV1']]);

        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/commands')
            || str_contains($request->url(), '/v1/devices/control'));
    }

    public function test_apply_returns_409_while_the_group_lock_is_held(): void
    {
        $this->fakeProviders();
        config(['lighting.control_lock_wait' => 0]);
        $scene = $this->makeScene('Off', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']]);

        $lock = Cache::lock('lighting:control', 10);
        $this->assertTrue($lock->get());

        try {
            $this->postJson(route('lighting.scenes.apply', $scene))->assertStatus(409);
        } finally {
            $lock->release();
        }
    }

    public function test_page_lists_user_scenes_after_built_ins_and_marks_the_active_one(): void
    {
        $this->fakeProviders();
        // Matches the live state: t1 warm white 70 %, t3 off. The offline lamp is ignored.
        $this->makeScene('Evening', [
            ['provider' => 'tuya', 'light_id' => 't1', 'power' => true, 'brightness' => 70, 'mode' => 'white', 'color_temp' => 10],
            ['provider' => 'tuya', 'light_id' => 't3', 'power' => false, 'mode' => 'white'],
            ['provider' => 'tuya', 'light_id' => 't4', 'power' => true, 'brightness' => 10, 'mode' => 'white'],
        ], 1);
        $this->makeScene('Gaming', [['provider' => 'tuya', 'light_id' => 't1', 'power' => false, 'mode' => 'white']], 2);

        $response = $this->get(route('lighting.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Night light', 'Evening', 'Gaming', 'Save scene']);
        $response->assertSee('3 lamps · your scene');
        $response->assertSee('Save as scene…');
        $response->assertSeeHtml('data-scenes-url="'.route('lighting.scenes.store').'"');
        $content = (string) $response->getContent();
        $this->assertMatchesRegularExpression('/data-scene-label="Evening"[^>]*aria-pressed="true"/s', $content);
        $this->assertMatchesRegularExpression('/data-scene-label="Gaming"[^>]*aria-pressed="false"/s', $content);
    }
}
