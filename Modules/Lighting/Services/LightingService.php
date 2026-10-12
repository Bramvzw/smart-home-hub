<?php

namespace Modules\Lighting\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Lighting\Contracts\LightProvider;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Data\LightingGroupResult;
use Modules\Lighting\Data\LightingPresetResult;
use Modules\Lighting\Data\LightingSnapshot;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Data\WeatherPresetTrigger;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Exceptions\UnknownLight;
use Modules\Lighting\Exceptions\UnknownLightingPreset;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Modules\Lighting\Services\Providers\TuyaProvider;
use RuntimeException;
use Throwable;

class LightingService
{
    /** Lock for writes that touch several lamps (presets, group writes). */
    private const GROUP_LOCK = 'lighting:control';

    /** @var list<LightProvider> */
    private readonly array $providers;

    public function __construct(TuyaProvider $tuya, GoveeProvider $govee)
    {
        $this->providers = [$tuya, $govee];
    }

    public function isConfigured(): bool
    {
        return $this->configuredProviders() !== [];
    }

    /** Unreachable providers are reported separately; the others still render. */
    public function snapshot(): LightingSnapshot
    {
        $lights = [];
        $unreachable = [];

        foreach ($this->configuredProviders() as $provider) {
            try {
                $lights = array_merge($lights, $this->cachedLights($provider));
            } catch (Throwable $e) {
                // Log RuntimeException messages (provider-controlled); for other throwables only the class to avoid leaking transport internals.
                Log::warning('Lighting provider unreachable', [
                    'provider' => $provider->label(),
                    'reason' => $e instanceof RuntimeException ? $e->getMessage() : $e::class,
                ]);
                $unreachable[] = $provider->label();
            }
        }

        return new LightingSnapshot($lights, array_values(array_unique($unreachable)));
    }

    /**
     * Apply changes to one light and return its state after the write.
     *
     * The current mode, colour and brightness come from the snapshot cache
     * (one device-list read on a miss) instead of a live status read, and
     * the provider sends the whole change set in as few calls as it can.
     * The cached snapshot is then updated with the written state, so
     * consecutive slider moves stay one provider call each.
     *
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     *
     * @throws UnknownLight
     */
    public function control(string $providerKey, string $id, array $changes): Light
    {
        // Per-light lock: a command for one lamp never queues behind another lamp.
        return $this->withControlLock(self::GROUP_LOCK.':'.$providerKey.':'.$id, function () use ($providerKey, $id, $changes): Light {
            $provider = $this->providerByKey($providerKey);
            $light = $this->findLight($provider, $id) ?? throw UnknownLight::forTarget($providerKey, $id);

            $provider->applyChanges($light, $changes);

            $updated = $light->withChanges($changes);
            $this->rememberWrites($provider, [$updated]);

            return $updated;
        });
    }

    public function applyPreset(string $key): LightingPresetResult
    {
        return $this->withControlLock(self::GROUP_LOCK, function () use ($key): LightingPresetResult {
            $preset = $this->presetByKey($key);
            $applied = 0;
            $skipped = 0;
            $failed = [];

            foreach ($this->configuredProviders() as $provider) {
                try {
                    $lights = $this->cachedLights($provider);
                } catch (Throwable) {
                    $failed[] = $provider->label();

                    continue;
                }

                $written = [];
                foreach ($lights as $light) {
                    if (! $preset->targetsLight($light)) {
                        $skipped++;

                        continue;
                    }

                    if (! $light->reachable) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $this->applyPresetToLight($provider, $light, $preset);
                        $written[] = $light->withChanges($this->presetChanges($preset, $light));
                        $applied++;
                    } catch (Throwable) {
                        $failed[] = $light->name;
                    }
                }

                $this->rememberWrites($provider, $written);
            }

            return new LightingPresetResult($preset, $applied, $skipped, array_values(array_unique($failed)));
        });
    }

    /**
     * Apply one change set to several lights at once. Every target is resolved
     * against the cached snapshot first, so an unknown light rejects the whole
     * request before anything is written.
     *
     * @param  list<array{provider: string, id: string}>  $targets
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     *
     * @throws UnknownLight
     */
    public function controlLights(array $targets, array $changes): LightingGroupResult
    {
        return $this->withControlLock(self::GROUP_LOCK, function () use ($targets, $changes): LightingGroupResult {
            $failed = [];
            $batches = [];

            foreach ($this->idsByProvider($targets) as $providerKey => $ids) {
                $provider = $this->configuredProviderByKey($providerKey);
                if ($provider === null) {
                    throw UnknownLight::forTarget($providerKey, $ids[0]);
                }

                try {
                    $lights = $this->cachedLights($provider);
                } catch (Throwable) {
                    $failed[] = $provider->label();

                    continue;
                }

                $byId = [];
                foreach ($lights as $light) {
                    $byId[$light->id] = $light;
                }

                $reachable = [];
                foreach ($ids as $id) {
                    $light = $byId[$id] ?? throw UnknownLight::forTarget($providerKey, $id);

                    if ($light->reachable) {
                        $reachable[] = $light;
                    } else {
                        $failed[] = $light->name;
                    }
                }

                $batches[] = [$provider, $reachable];
            }

            $applied = 0;
            foreach ($batches as [$provider, $lights]) {
                if ($lights === []) {
                    continue;
                }

                try {
                    $failedIds = $provider->applyToLights($lights, $changes);
                } catch (Throwable) {
                    // Provider-wide failure (e.g. token refresh): every lamp of it failed.
                    $failedIds = array_map(static fn (Light $light): string => $light->id, $lights);
                }

                $written = [];
                foreach ($lights as $light) {
                    if (in_array($light->id, $failedIds, true)) {
                        $failed[] = $light->name;
                    } else {
                        $written[] = $light->withChanges(['power' => $changes['power'] ?? true] + $changes);
                        $applied++;
                    }
                }

                $this->rememberWrites($provider, $written);
            }

            return new LightingGroupResult($applied, array_values(array_unique($failed)));
        });
    }

    /**
     * Bring several lamps into their own target states (user scenes). Lamps
     * that share a provider and an identical change set go out as one group
     * write, so Calex lamps are still written concurrently. Unreachable lamps
     * are skipped and reported in `failed`; lamps that no longer exist are
     * ignored.
     *
     * @param  list<SceneLightState>  $states
     */
    public function applyLightStates(array $states): LightingGroupResult
    {
        return $this->withControlLock(self::GROUP_LOCK, function () use ($states): LightingGroupResult {
            $failed = [];
            $applied = 0;

            $byProvider = [];
            foreach ($states as $state) {
                $byProvider[$state->provider][] = $state;
            }

            foreach ($byProvider as $providerKey => $providerStates) {
                $provider = $this->configuredProviderByKey($providerKey);
                if ($provider === null) {
                    continue;
                }

                try {
                    $lights = $this->cachedLights($provider);
                } catch (Throwable) {
                    $failed[] = $provider->label();

                    continue;
                }

                $byId = [];
                foreach ($lights as $light) {
                    $byId[$light->id] = $light;
                }

                /** @var array<string, array{changes: array{power: bool, brightness?: int, color?: string, color_temp?: int}, lights: list<Light>}> $batches */
                $batches = [];
                foreach ($providerStates as $state) {
                    $light = $byId[$state->id] ?? null;
                    if ($light === null) {
                        continue;
                    }
                    if (! $light->reachable) {
                        $failed[] = $light->name;

                        continue;
                    }

                    $changes = $state->changes();
                    $batchKey = (string) json_encode($changes);
                    $batches[$batchKey]['changes'] = $changes;
                    $batches[$batchKey]['lights'][] = $light;
                }

                $written = [];
                foreach ($batches as $batch) {
                    try {
                        $failedIds = $provider->applyToLights($batch['lights'], $batch['changes']);
                    } catch (Throwable) {
                        $failedIds = array_map(static fn (Light $light): string => $light->id, $batch['lights']);
                    }

                    foreach ($batch['lights'] as $light) {
                        if (in_array($light->id, $failedIds, true)) {
                            $failed[] = $light->name;
                        } else {
                            $written[] = $light->withChanges($batch['changes']);
                            $applied++;
                        }
                    }
                }

                $this->rememberWrites($provider, $written);
            }

            return new LightingGroupResult($applied, array_values(array_unique($failed)));
        });
    }

    /**
     * @return list<LightPreset>
     */
    public function presets(): array
    {
        return array_map(
            fn (string $key, array $preset): LightPreset => $this->makePreset($key, $preset),
            array_keys((array) config('lighting.presets', [])),
            array_values((array) config('lighting.presets', [])),
        );
    }

    /**
     * @return list<LightProvider>
     */
    public function configuredProviders(): array
    {
        return array_values(array_filter($this->providers, static fn (LightProvider $p): bool => $p->isConfigured()));
    }

    /**
     * The provider's lights from the snapshot cache, read from the provider
     * on a miss. The entry carries its own expiry so writes can update it
     * without extending its lifetime.
     *
     * @return list<Light>
     */
    private function cachedLights(LightProvider $provider): array
    {
        $cached = Cache::get($this->cacheKey($provider));
        if (is_array($cached) && isset($cached['lights'], $cached['expires_at'])) {
            return $cached['lights'];
        }

        $ttl = max(1, (int) config('lighting.cache_ttl', 30));
        $lights = $provider->lights();
        Cache::put($this->cacheKey($provider), ['lights' => $lights, 'expires_at' => time() + $ttl], $ttl);

        return $lights;
    }

    private function findLight(LightProvider $provider, string $id): ?Light
    {
        foreach ($this->cachedLights($provider) as $light) {
            if ($light->id === $id) {
                return $light;
            }
        }

        return null;
    }

    /**
     * Put the written state of these lights into the cached snapshot instead
     * of forgetting it: the next write needs no provider read and the poll
     * shows the new state. The original expiry stays, so the snapshot is
     * still re-read from the provider on schedule.
     *
     * @param  list<Light>  $written
     */
    private function rememberWrites(LightProvider $provider, array $written): void
    {
        if ($written === []) {
            return;
        }

        $key = $this->cacheKey($provider);

        try {
            // Single-light writes to different lamps run in parallel; serialise the read-modify-write.
            Cache::lock($key.':write', 5)->block(2, static function () use ($key, $written): void {
                $cached = Cache::get($key);
                $remaining = is_array($cached) && isset($cached['lights'], $cached['expires_at'])
                    ? (int) $cached['expires_at'] - time()
                    : 0;
                if ($remaining <= 0) {
                    return;
                }

                $byId = [];
                foreach ($written as $light) {
                    $byId[$light->id] = $light;
                }

                $lights = array_map(static fn (Light $light): Light => $byId[$light->id] ?? $light, $cached['lights']);
                Cache::put($key, ['lights' => $lights, 'expires_at' => $cached['expires_at']], $remaining);
            });
        } catch (LockTimeoutException) {
            // Could not update safely: drop the snapshot so the next read is fresh.
            Cache::forget($key);
        }
    }

    /**
     * The change set a preset writes to one lamp (mirrors applyPresetToLight()).
     *
     * @return array{power: bool, brightness?: int, color?: string, color_temp?: int}
     */
    private function presetChanges(LightPreset $preset, Light $light): array
    {
        $changes = ['power' => $preset->power];
        if (! $preset->power) {
            return $changes;
        }
        if ($preset->brightness !== null) {
            $changes['brightness'] = $preset->brightness;
        }
        if ($light->supportsColor && $preset->color !== null) {
            $changes['color'] = $preset->color;
        }
        if ($light->supportsColorTemp && $preset->colorTemp !== null) {
            $changes['color_temp'] = $preset->colorTemp;
        }

        return $changes;
    }

    private function applyPresetToLight(LightProvider $provider, Light $light, LightPreset $preset): void
    {
        // Always apply the preset's values rather than skipping when the (possibly
        // stale, cached) current value already matches — selecting a preset should
        // deterministically overwrite the lamp's state. The target state is fully
        // known, so providers can batch it into as few calls as possible.
        $provider->applyState(
            $light->id,
            $preset->power,
            $preset->brightness,
            $light->supportsColor ? $preset->color : null,
            $light->supportsColorTemp ? $preset->colorTemp : null,
        );
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function withControlLock(string $lock, Closure $callback): mixed
    {
        try {
            return Cache::lock(
                $lock,
                max(1, (int) config('lighting.control_lock_ttl', 20)),
            )->block(
                max(0, (int) config('lighting.control_lock_wait', 8)),
                $callback,
            );
        } catch (LockTimeoutException $exception) {
            throw LightingControlBusy::queueTimeout($exception);
        }
    }

    private function presetByKey(string $key): LightPreset
    {
        $presets = (array) config('lighting.presets', []);
        $preset = $presets[$key] ?? null;

        if (! is_array($preset)) {
            throw UnknownLightingPreset::forKey($key);
        }

        return $this->makePreset($key, $preset);
    }

    private function makePreset(string $key, array $preset): LightPreset
    {
        $color = isset($preset['color']) ? (string) $preset['color'] : null;

        if (is_string($color) && $color !== '' && ! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        return new LightPreset(
            key: $key,
            label: (string) ($preset['label'] ?? ucfirst($key)),
            power: (bool) ($preset['power'] ?? true),
            brightness: isset($preset['brightness']) ? max(0, min(100, (int) $preset['brightness'])) : null,
            color: $color,
            targetNameContains: $this->targetNameContains($preset),
            weatherTrigger: $this->weatherTrigger($preset),
            colorTemp: isset($preset['color_temp']) ? max(0, min(100, (int) $preset['color_temp'])) : null,
        );
    }

    private function weatherTrigger(array $preset): ?WeatherPresetTrigger
    {
        $config = $preset['weather_trigger'] ?? null;

        if (! is_array($config)) {
            return null;
        }

        return new WeatherPresetTrigger(
            enabled: (bool) ($config['enabled'] ?? false),
            rainProbabilityMin: isset($config['rain_probability_min']) ? max(0, min(100, (int) $config['rain_probability_min'])) : null,
            precipitationMinMm: isset($config['precipitation_min_mm']) ? max(0.0, (float) $config['precipitation_min_mm']) : null,
            temperatureMax: isset($config['temperature_max']) ? (float) $config['temperature_max'] : null,
            startTime: (string) ($config['start_time'] ?? '00:00'),
            endTime: (string) ($config['end_time'] ?? '23:59'),
        );
    }

    /**
     * @return list<string>
     */
    private function targetNameContains(array $preset): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $target): string => strtolower(trim((string) $target)),
                (array) ($preset['target_name_contains'] ?? []),
            ),
            static fn (string $target): bool => $target !== '',
        ));
    }

    private function providerByKey(string $key): LightProvider
    {
        return $this->configuredProviderByKey($key)
            ?? throw new RuntimeException('Unknown or unconfigured lighting provider.');
    }

    private function configuredProviderByKey(string $key): ?LightProvider
    {
        foreach ($this->configuredProviders() as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Group targets per provider, dropping duplicates.
     *
     * @param  list<array{provider: string, id: string}>  $targets
     * @return array<string, non-empty-list<string>>
     */
    private function idsByProvider(array $targets): array
    {
        $grouped = [];
        foreach ($targets as $target) {
            if (! in_array($target['id'], $grouped[$target['provider']] ?? [], true)) {
                $grouped[$target['provider']][] = $target['id'];
            }
        }

        return $grouped;
    }

    private function cacheKey(LightProvider $provider): string
    {
        return 'lighting:lights:'.$provider->key();
    }
}
