<?php

namespace Modules\Lighting\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LightingPageTest extends TestCase
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
     * @param  array<string, mixed>  $overrides  extra Http::fake patterns, matched first
     */
    private function fakeProviders(array $overrides = []): void
    {
        Http::fake($overrides + [
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 't1', 'name' => 'Living spot', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => true],
                    ['code' => 'work_mode', 'value' => 'white'],
                    ['code' => 'bright_value_v2', 'value' => 700],
                    ['code' => 'temp_value_v2', 'value' => 100],
                    ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 30, 's' => 800, 'v' => 1000])],
                ]],
                ['id' => 't2', 'name' => 'Hallway spot', 'online' => false, 'status' => [
                    ['code' => 'switch_led', 'value' => false],
                    ['code' => 'bright_value_v2', 'value' => 300],
                ]],
                ['id' => 't3', 'name' => 'Plain bulb', 'online' => true, 'status' => [
                    ['code' => 'switch_led', 'value' => false],
                    ['code' => 'bright_value_v2', 'value' => 500],
                ]],
            ]]]),
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'LED strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'on'], ['brightness' => 40], ['color' => ['r' => 127, 'g' => 150, 'b' => 255]],
            ]]]),
        ]);
    }

    public function test_page_renders_a_tile_for_every_light(): void
    {
        $this->fakeProviders();
        $response = $this->get(route('lighting.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Living spot', 'Hallway spot', 'Plain bulb', 'LED strip']);
        $this->assertSame(4, substr_count((string) $response->getContent(), 'class="lighting-tile"'));

        // Header: on-count, Select and All off (only in the header).
        $response->assertSee('2 of 4 on');
        $response->assertSee('Select');
        $response->assertSeeHtml('data-preset="off"');
        $response->assertSee('All off');

        // Scene strip lists the built-ins without "All off".
        $response->assertSeeInOrder(['Bright', 'Cozy', 'Movie', 'Night', 'Night light']);
        $response->assertSeeHtml('aria-label="Scenes"');
        $this->assertSame(1, substr_count((string) $response->getContent(), 'data-preset="off"'));

        // State lines and provider labels.
        $response->assertSee('70% · Warm white');
        $response->assertSee('40% · Colour');
        $response->assertSeeHtml('data-provider-label="Calex"');
        $response->assertSeeHtml('data-provider-label="Govee"');

        // Endpoints the page script uses.
        $response->assertSeeHtml('data-group-url="'.route('lighting.lights.update-many').'"');
        $response->assertSeeHtml('data-state-url="'.route('lighting.state').'"');
        $response->assertSeeHtml('data-poll-seconds="120"');
        $response->assertSeeHtml('aria-label="Controls for Living spot"');
        $response->assertSeeHtml('aria-label="Brightness of LED strip"');
        $response->assertSeeHtml('Lamp controls');

        // The control sheet, the scene menu and the save dialog are modal.
        $this->assertSame(3, substr_count((string) $response->getContent(), 'aria-modal="true"'));
    }

    public function test_unreachable_light_is_shown_offline_without_actions(): void
    {
        $this->fakeProviders();
        $content = (string) $this->get(route('lighting.index'))->assertStatus(200)->getContent();

        $tile = $this->tileMarkup($content, 'tuya::t2');
        $this->assertStringContainsString('data-reachable="false"', $tile);
        $this->assertStringContainsString('Offline', $tile);
        $this->assertMatchesRegularExpression('/data-tile-toggle[^>]*disabled/s', $tile);
    }

    public function test_unsupported_capabilities_are_flagged_on_the_tile(): void
    {
        $this->fakeProviders();
        $content = (string) $this->get(route('lighting.index'))->assertStatus(200)->getContent();

        $plain = $this->tileMarkup($content, 'tuya::t3');
        $this->assertStringContainsString('data-supports-color="false"', $plain);
        $this->assertStringContainsString('data-supports-color-temp="false"', $plain);
        $this->assertStringContainsString('>Off<', $plain);

        $strip = $this->tileMarkup($content, 'govee::DEV1');
        $this->assertStringContainsString('data-supports-color="true"', $strip);
        $this->assertStringContainsString('data-supports-color-temp="false"', $strip);
        $this->assertStringContainsString('data-mode="colour"', $strip);
    }

    public function test_active_built_in_preset_is_marked_on_render(): void
    {
        $this->fakeProviders([
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => []]]),
            '*developer-api.govee.com/v1/devices' => Http::response(['code' => 200, 'data' => ['devices' => [
                ['device' => 'DEV1', 'model' => 'H6159', 'deviceName' => 'LED strip', 'controllable' => true, 'supportCmds' => ['turn', 'brightness', 'color']],
            ]]]),
            '*/v1/devices/state*' => Http::response(['code' => 200, 'data' => ['properties' => [
                ['online' => true], ['powerState' => 'off'], ['brightness' => 40],
            ]]]),
        ]);

        $content = (string) $this->get(route('lighting.index'))->assertStatus(200)->getContent();

        $this->assertMatchesRegularExpression('/data-preset="off"[^>]*aria-pressed="true"/s', $content);
    }

    private function tileMarkup(string $content, string $key): string
    {
        $start = strpos($content, 'data-light-key="'.$key.'"');
        $this->assertNotFalse($start, "No tile for {$key}");
        $end = strpos($content, '</article>', $start);

        return substr($content, $start, (int) $end - $start);
    }
}
