<?php

namespace Modules\Lighting\Tests\Unit;

use Closure;
use Illuminate\Cache\ArrayStore;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Services\LightingService;
use Tests\TestCase;

/**
 * Interleaving of single-light writes with group writes and snapshot refills.
 * The cache store runs a hook the moment a lock is requested, which is where
 * a concurrent request would have run in production.
 */
class LightingWriteRaceTest extends TestCase
{
    /** @var array<string, Closure(): void> */
    private array $onLock = [];

    protected function setUp(): void
    {
        parent::setUp();

        $hooks = &$this->onLock;
        $store = new class($hooks) extends ArrayStore
        {
            /** @param  array<string, Closure(): void>  $hooks */
            public function __construct(private array &$hooks)
            {
                parent::__construct();
            }

            public function lock($name, $seconds = 0, $owner = null)
            {
                if (isset($this->hooks[$name])) {
                    $hook = $this->hooks[$name];
                    unset($this->hooks[$name]);
                    $hook();
                }

                return parent::lock($name, $seconds, $owner);
            }
        };

        Cache::extend('racy', fn () => Cache::repository($store));
        config(['cache.stores.racy' => ['driver' => 'racy']]);
        Cache::setDefaultDriver('racy');
        config([
            'lighting.tuya' => ['client_id' => 'cid', 'client_secret' => 'sec', 'region' => 'eu', 'uid' => 'u1'],
            'lighting.govee' => ['api_key' => ''],
            'lighting.presets' => ['dim' => ['label' => 'Dim', 'power' => true, 'brightness' => 30]],
        ]);
    }

    /**
     * Spot (d1) shows red at 80 % in colour mode.
     *
     * @param  (Closure(): void)|null  $duringDeviceRead  runs inside the first device-list read
     */
    private function fakeTuya(?Closure $duringDeviceRead = null): void
    {
        Http::fake([
            '*/v1.0/token*' => Http::response(['success' => true, 'result' => ['access_token' => 'tok', 'expire_time' => 7200]]),
            '*associated-users/devices*' => function () use (&$duringDeviceRead) {
                if ($duringDeviceRead !== null) {
                    $hook = $duringDeviceRead;
                    $duringDeviceRead = null;
                    $hook();
                }

                return Http::response(['success' => true, 'result' => ['devices' => [
                    ['id' => 'd1', 'name' => 'Spot', 'online' => true, 'status' => [
                        ['code' => 'switch_led', 'value' => true],
                        ['code' => 'work_mode', 'value' => 'colour'],
                        ['code' => 'bright_value_v2', 'value' => 1000],
                        ['code' => 'temp_value_v2', 'value' => 500],
                        ['code' => 'colour_data_v2', 'value' => json_encode(['h' => 0, 's' => 1000, 'v' => 800])],
                    ]],
                ]]]);
            },
            '*/v1.0/devices/*/commands' => Http::response(['success' => true, 'result' => true]),
        ]);
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

    /** A single-light write that turns the spot green lands while the next writer waits for the spot's lock. */
    private function greenWriteWhileWaiting(LightingService $service): void
    {
        $this->onLock['lighting:control:tuya:d1'] = static function () use ($service): void {
            $service->control('tuya', 'd1', ['color' => '#00ff00']);
        };
    }

    private function assertSpotEndsGreenAt30(LightingService $service): void
    {
        $this->assertSame(
            ['code' => 'colour_data_v2', 'value' => ['h' => 120, 's' => 1000, 'v' => 300]],
            $this->commandBatches()[1][2] ?? null,
            'The group write must re-send the colour the single write just set.',
        );

        $spot = $service->snapshot()->lights[0];
        $this->assertSame(LightMode::Colour, $spot->mode);
        $this->assertSame('#00ff00', $spot->color);
        $this->assertSame(30, $spot->brightness);
    }

    public function test_a_group_write_plans_from_the_state_a_waiting_single_write_left(): void
    {
        $this->fakeTuya();
        $service = app(LightingService::class);
        $service->snapshot();
        $this->greenWriteWhileWaiting($service);

        $result = $service->controlLights([['provider' => 'tuya', 'id' => 'd1']], ['brightness' => 30]);

        $this->assertSame(1, $result->applied);
        $this->assertSpotEndsGreenAt30($service);
    }

    public function test_a_scene_plans_from_the_state_a_waiting_single_write_left(): void
    {
        $this->fakeTuya();
        $service = app(LightingService::class);
        $service->snapshot();
        $this->greenWriteWhileWaiting($service);

        $service->applyLightStates([new SceneLightState('tuya', 'd1', true, 30)]);

        $this->assertSpotEndsGreenAt30($service);
    }

    public function test_a_preset_does_not_cache_the_state_from_before_a_waiting_single_write(): void
    {
        $this->fakeTuya();
        $service = app(LightingService::class);
        $service->snapshot();
        $this->greenWriteWhileWaiting($service);

        $service->applyPreset('dim');

        // The preset sends power + brightness only; the colour from the single write stays.
        $spot = $service->snapshot()->lights[0];
        $this->assertSame('#00ff00', $spot->color);
        $this->assertSame(30, $spot->brightness);
    }

    public function test_a_snapshot_refill_keeps_a_write_that_landed_during_the_provider_read(): void
    {
        $service = app(LightingService::class);
        // The poll misses the cache and reads the provider; meanwhile a write
        // to the spot runs (it reads and caches its own snapshot first).
        $this->fakeTuya(static function () use ($service): void {
            $service->control('tuya', 'd1', ['color' => '#00ff00']);
        });

        $polled = $service->snapshot()->lights[0];

        $this->assertSame('#00ff00', $polled->color, 'The poll must not return the pre-write colour.');
        $cached = $service->snapshot()->lights[0];
        $this->assertSame('#00ff00', $cached->color, 'The refill must not cache the pre-write colour.');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/v1.0/devices/d1/commands'));
    }

    public function test_a_write_older_than_the_refill_read_does_not_override_it(): void
    {
        $this->fakeTuya();
        $service = app(LightingService::class);
        $service->control('tuya', 'd1', ['color' => '#00ff00']);
        Cache::forget('lighting:lights:tuya');

        // The provider still reports red: a later change in the Smart Life app wins over the older write.
        $this->assertSame('#ff0000', $service->snapshot()->lights[0]->color);
    }
}
