<?php

namespace Modules\Lighting\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Services\LightingService;
use Tests\TestCase;

/**
 * Single-light writes: one batched Tuya command built from the cached
 * snapshot, no live status reads, and an accurate returned Light.
 */
class SingleLightControlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'lighting.tuya' => ['client_id' => 'cid', 'client_secret' => 'sec', 'region' => 'eu', 'uid' => 'u1'],
            'lighting.govee' => ['api_key' => ''],
        ]);
    }

    /**
     * @param  list<array{code: string, value: mixed}>  $status
     */
    private function fakeTuyaLamp(array $status): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => Http::response(['success' => true, 'result' => ['devices' => [
                ['id' => 'd1', 'name' => 'Spot', 'online' => true, 'status' => $status],
            ]]]),
            '*/v1.0/devices/*/status' => Http::response(['success' => true, 'result' => $status]),
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
        ]);
    }

    /** @return list<array{code: string, value: mixed}> */
    private function whiteLamp(int $bright = 100): array
    {
        return [
            ['code' => 'switch_led', 'value' => true],
            ['code' => 'work_mode', 'value' => 'white'],
            ['code' => 'bright_value_v2', 'value' => $bright],
            ['code' => 'temp_value_v2', 'value' => 0],
            ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 30, 's' => 1000, 'v' => 1000])],
        ];
    }

    /** @return list<array{code: string, value: mixed}> */
    private function colourLamp(int $v = 250): array
    {
        return [
            ['code' => 'switch_led', 'value' => true],
            ['code' => 'work_mode', 'value' => 'colour'],
            ['code' => 'bright_value_v2', 'value' => 1000],
            ['code' => 'temp_value_v2', 'value' => 1000],
            ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 240, 's' => 1000, 'v' => $v])],
        ];
    }

    /** @return array<int, array<int, mixed>> */
    private function commandBatches(): array
    {
        return Http::recorded()
            ->filter(fn (array $pair): bool => str_contains((string) $pair[0]->url(), '/v1.0/devices/d1/commands'))
            ->map(fn (array $pair): array => (array) $pair[0]['commands'])
            ->values()
            ->all();
    }

    public function test_power_brightness_and_colour_go_out_as_one_batch_on_a_white_lamp(): void
    {
        $this->fakeTuyaLamp($this->whiteLamp(1000));

        app(LightingService::class)->control('tuya', 'd1', ['power' => true, 'brightness' => 20, 'color' => '#ff0000']);

        $this->assertSame([[
            ['code' => 'switch_led', 'value' => true],
            ['code' => 'work_mode', 'value' => 'colour'],
            ['code' => 'colour_data_v2', 'value' => ['h' => 0, 's' => 1000, 'v' => 200]],
        ]], $this->commandBatches());
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/status'));
    }

    public function test_brightness_and_colour_temperature_go_out_as_one_white_batch_on_a_colour_lamp(): void
    {
        $this->fakeTuyaLamp($this->colourLamp());

        app(LightingService::class)->control('tuya', 'd1', ['brightness' => 35, 'color_temp' => 40]);

        $this->assertSame([[
            ['code' => 'work_mode', 'value' => 'white'],
            ['code' => 'temp_value_v2', 'value' => 400],
            ['code' => 'bright_value_v2', 'value' => 350],
        ]], $this->commandBatches());
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/status'));
    }

    public function test_a_colour_alone_keeps_the_dim_white_brightness(): void
    {
        $this->fakeTuyaLamp($this->whiteLamp(10));

        app(LightingService::class)->control('tuya', 'd1', ['color' => '#00ff00']);

        $this->assertSame(10, $this->commandBatches()[0][1]['value']['v']);
    }

    public function test_consecutive_writes_cost_one_call_each_and_use_the_written_mode(): void
    {
        $this->fakeTuyaLamp($this->whiteLamp(500));
        $service = app(LightingService::class);

        $service->control('tuya', 'd1', ['power' => true, 'color' => '#0000ff']);
        $service->control('tuya', 'd1', ['power' => true, 'brightness' => 30]);

        // One device-list read for both writes, no status reads.
        Http::assertSentCount(4); // token, device list, two commands
        $batches = $this->commandBatches();
        $this->assertCount(2, $batches);
        // The second write knows the lamp is now in colour mode: V moves, no flip to white.
        $this->assertSame([
            ['code' => 'switch_led', 'value' => true],
            ['code' => 'work_mode', 'value' => 'colour'],
            ['code' => 'colour_data_v2', 'value' => ['h' => 240, 's' => 1000, 'v' => 300]],
        ], $batches[1]);

        $snapshot = $service->snapshot()->lights[0];
        $this->assertSame(LightMode::Colour, $snapshot->mode);
        $this->assertSame(30, $snapshot->brightness);
    }

    public function test_the_returned_light_is_the_cached_lamp_with_the_change_applied(): void
    {
        $this->fakeTuyaLamp($this->colourLamp(250));

        $light = app(LightingService::class)->control('tuya', 'd1', ['power' => false]);

        $this->assertSame([[['code' => 'switch_led', 'value' => false]]], $this->commandBatches());
        $this->assertFalse($light->on);
        $this->assertSame('Spot', $light->name);
        $this->assertSame(25, $light->brightness);
        $this->assertSame(LightMode::Colour, $light->mode);
        $this->assertSame('#0000ff', $light->color);
        $this->assertTrue($light->supportsColor);
        $this->assertTrue($light->supportsColorTemp);
    }
}
