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
    /** Shared lock for writes that touch several lamps (presets, group writes, scenes); per-light locks hang below it. */
    private const GROUP_LOCK = 'lighting:control';

    /** Seconds a written lamp state stays in the write log that guards snapshot refills (longer than any provider read). */
    private const WRITE_LOG_TTL = 120;

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
        return $this->withControlLock($this->lightLockKey($providerKey, $id), function () use ($providerKey, $id, $changes): Light {
            $provider = $this->configuredProviderByKey($providerKey) ?? throw UnknownLight::forTarget($providerKey, $id);
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

            // A first read only decides which lamps to lock; the plan is built
            // from a fresh read once the locks are held, so a single-light
            // write that finished meanwhile is not overwritten with stale state.
            $locked = [];
            $unreadable = [];
            foreach ($this->configuredProviders() as $provider) {
                try {
                    foreach ($this->cachedLights($provider) as $light) {
                        $locked[$this->lightLockKey($light->provider, $light->id)] = true;
                    }
                } catch (Throwable $e) {
                    $this->logWriteFailure($provider, $e);
                    $unreadable[$provider->key()] = true;
                }
            }

            return $this->withLightLocks(array_keys($locked), function () use ($preset, $locked, $unreadable): LightingPresetResult {
                $applied = 0;
                $skipped = 0;
                $failed = [];

                foreach ($this->configuredProviders() as $provider) {
                    try {
                        $lights = isset($unreadable[$provider->key()]) ? null : $this->cachedLights($provider);
                    } catch (Throwable $e) {
                        $this->logWriteFailure($provider, $e);
                        $lights = null;
                    }
                    if ($lights === null) {
                        $failed[] = $provider->label();

                        continue;
                    }

                    $written = [];
                    foreach ($lights as $light) {
                        if (! $preset->targetsLight($light) || ! $light->reachable || ! isset($locked[$this->lightLockKey($light->provider, $light->id)])) {
                            $skipped++;

                            continue;
                        }

                        try {
                            $this->applyPresetToLight($provider, $light, $preset);
                            $written[] = $light->withChanges($this->presetChanges($preset, $light));
                            $applied++;
                        } catch (Throwable $e) {
                            $this->logWriteFailure($provider, $e);
                            $failed[] = $light->name;
                        }
                    }

                    $this->rememberWrites($provider, $written);
                }

                return new LightingPresetResult($preset, $applied, $skipped, array_values(array_unique($failed)));
            });
        });
    }

    /**
     * Apply one change set to several lights at once. Every target is resolved
     * against the cached snapshot first, so an unknown light rejects the whole
     * request before anything is written. Lamps that cannot show the requested
     * colour or colour temperature get only the rest of the change set; when
     * nothing else is left they are left untouched and listed in `skipped`.
     *
     * @param  list<array{provider: string, id: string}>  $targets
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     *
     * @throws UnknownLight
     */
    public function controlLights(array $targets, array $changes): LightingGroupResult
    {
        $lockKeys = array_map(fn (array $target): string => $this->lightLockKey($target['provider'], $target['id']), $targets);

        // Lock first, then read and plan: see applyPreset().
        return $this->withControlLock(self::GROUP_LOCK, fn (): LightingGroupResult => $this->withLightLocks($lockKeys, function () use ($targets, $changes): LightingGroupResult {
            $failed = [];
            $skipped = [];
            $plan = [];

            foreach ($this->idsByProvider($targets) as $providerKey => $ids) {
                $provider = $this->configuredProviderByKey($providerKey);
                if ($provider === null) {
                    throw UnknownLight::forTarget($providerKey, $ids[0]);
                }

                try {
                    $lights = $this->cachedLights($provider);
                } catch (Throwable $e) {
                    $this->logWriteFailure($provider, $e);
                    $failed[] = $provider->label();

                    continue;
                }

                $byId = [];
                foreach ($lights as $light) {
                    $byId[$light->id] = $light;
                }

                $batches = [];
                foreach ($ids as $id) {
                    $light = $byId[$id] ?? throw UnknownLight::forTarget($providerKey, $id);

                    if (! $light->reachable) {
                        $failed[] = $light->name;

                        continue;
                    }

                    $supported = $this->supportedChanges($light, $changes);
                    if ($supported === null) {
                        $skipped[] = $light->name;

                        continue;
                    }

                    $batchKey = (string) json_encode($supported);
                    $batches[$batchKey]['changes'] = $supported;
                    $batches[$batchKey]['lights'][] = $light;
                }

                $plan[] = [$provider, array_values($batches)];
            }

            $applied = $this->writeBatches($plan, $failed);

            return new LightingGroupResult($applied, array_values(array_unique($failed)), array_values(array_unique($skipped)));
        }));
    }

    /**
     * Bring several lamps into their own target states (user scenes). Lamps
     * that share a provider and an identical change set go out as one group
     * write, so Calex lamps are still written concurrently. Unreachable lamps
     * are skipped and reported in `failed`; lamps that no longer exist (or
     * whose provider is not configured) are reported in `missingLights`.
     *
     * @param  list<SceneLightState>  $states
     */
    public function applyLightStates(array $states): LightingGroupResult
    {
        $lockKeys = array_map(fn (SceneLightState $state): string => $this->lightLockKey($state->provider, $state->id), $states);

        // Lock first, then read and plan: see applyPreset().
        return $this->withControlLock(self::GROUP_LOCK, fn (): LightingGroupResult => $this->withLightLocks($lockKeys, function () use ($states): LightingGroupResult {
            $failed = [];
            $skipped = [];
            $missing = [];
            $plan = [];

            $byProvider = [];
            foreach ($states as $state) {
                $byProvider[$state->provider][] = $state;
            }

            foreach ($byProvider as $providerKey => $providerStates) {
                $provider = $this->configuredProviderByKey($providerKey);
                if ($provider === null) {
                    foreach ($providerStates as $state) {
                        $missing[] = ['provider' => $state->provider, 'id' => $state->id];
                    }

                    continue;
                }

                try {
                    $lights = $this->cachedLights($provider);
                } catch (Throwable $e) {
                    $this->logWriteFailure($provider, $e);
                    $failed[] = $provider->label();

                    continue;
                }

                $byId = [];
                foreach ($lights as $light) {
                    $byId[$light->id] = $light;
                }

                $batches = [];
                foreach ($providerStates as $state) {
                    $light = $byId[$state->id] ?? null;
                    if ($light === null) {
                        $missing[] = ['provider' => $state->provider, 'id' => $state->id];

                        continue;
                    }
                    if (! $light->reachable) {
                        $failed[] = $light->name;

                        continue;
                    }

                    $changes = $this->supportedChanges($light, $state->changes());
                    if ($changes === null) {
                        $skipped[] = $light->name;

                        continue;
                    }

                    $batchKey = (string) json_encode($changes);
                    $batches[$batchKey]['changes'] = $changes;
                    $batches[$batchKey]['lights'][] = $light;
                }

                $plan[] = [$provider, array_values($batches)];
            }

            $applied = $this->writeBatches($plan, $failed);

            return new LightingGroupResult($applied, array_values(array_unique($failed)), array_values(array_unique($skipped)), $missing);
        }));
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
     * A write can land while the provider read is running (it then has no
     * snapshot to update), so before caching the fresh read every lamp
     * written after the read started is replaced by its written state.
     *
     * @return list<Light>
     */
    private function cachedLights(LightProvider $provider): array
    {
        $key = $this->cacheKey($provider);
        $cached = Cache::get($key);
        if (is_array($cached) && isset($cached['lights'], $cached['expires_at'])) {
            return $cached['lights'];
        }

        $ttl = max(1, (int) config('lighting.cache_ttl', 30));
        $readStartedAt = microtime(true);
        $lights = $provider->lights();

        try {
            // Same lock as rememberWrites(): no write slips in between the merge and the put.
            Cache::lock($key.':write', 5)->block(2, function () use ($key, $ttl, $readStartedAt, &$lights): void {
                $log = $this->writeLog($key);
                $lights = array_map(
                    static fn (Light $light): Light => isset($log[$light->id]) && $log[$light->id]['at'] >= $readStartedAt
                        ? $log[$light->id]['light']
                        : $light,
                    $lights,
                );
                Cache::put($key, ['lights' => $lights, 'expires_at' => time() + $ttl], $ttl);
            });
        } catch (LockTimeoutException) {
            // Not cached: the next read goes to the provider again.
        }

        return $lights;
    }

    /**
     * Recently written lamp states per lamp id, with the time of the write.
     *
     * @return array<string, array{light: Light, at: float}>
     */
    private function writeLog(string $key): array
    {
        $log = Cache::get($key.':writes');

        return is_array($log) ? $log : [];
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
            Cache::lock($key.':write', 5)->block(2, function () use ($key, $written): void {
                // Logged even without a snapshot, so a refill already running keeps this write.
                $now = microtime(true);
                $log = array_filter($this->writeLog($key), static fn (array $entry): bool => $entry['at'] > $now - self::WRITE_LOG_TTL);
                foreach ($written as $light) {
                    $log[$light->id] = ['light' => $light, 'at' => $now];
                }
                Cache::put($key.':writes', $log, self::WRITE_LOG_TTL);

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
                max(1, (int) config('lighting.control_lock_ttl', 90)),
            )->block(
                max(0, (int) config('lighting.control_lock_wait', 8)),
                $callback,
            );
        } catch (LockTimeoutException $exception) {
            throw LightingControlBusy::queueTimeout($exception);
        }
    }

    /**
     * Write planned group batches. Callers hold the per-light lock of every
     * lamp in them (taken before the plan was read), so a single-light write
     * on one of those lamps cannot interleave. Failed lamps are added to
     * `$failed`; returns the applied count.
     *
     * @param  list<array{0: LightProvider, 1: list<array{changes: array{power?: bool, brightness?: int, color?: string, color_temp?: int}, lights: list<Light>}>}>  $plan
     * @param  list<string>  $failed
     */
    private function writeBatches(array $plan, array &$failed): int
    {
        $applied = 0;
        foreach ($plan as [$provider, $batches]) {
            $written = [];
            foreach ($batches as $batch) {
                try {
                    $failedIds = $provider->applyToLights($batch['lights'], $batch['changes']);
                } catch (Throwable $e) {
                    // Provider-wide failure (e.g. token refresh): every lamp of the batch failed.
                    $this->logWriteFailure($provider, $e);
                    $failedIds = array_map(static fn (Light $light): string => $light->id, $batch['lights']);
                }

                if ($failedIds !== []) {
                    Log::warning('Lighting group write failed for some lamps', [
                        'provider' => $provider->label(),
                        'lamps' => count($failedIds),
                    ]);
                }

                foreach ($batch['lights'] as $light) {
                    if (in_array($light->id, $failedIds, true)) {
                        $failed[] = $light->name;
                    } else {
                        $written[] = $light->withChanges(['power' => $batch['changes']['power'] ?? true] + $batch['changes']);
                        $applied++;
                    }
                }
            }

            $this->rememberWrites($provider, $written);
        }

        return $applied;
    }

    /**
     * The part of a group change set this lamp can show, or null when the
     * request was only about a colour / colour temperature it cannot do —
     * then the lamp is skipped instead of silently being switched on.
     *
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     * @return array{power?: bool, brightness?: int, color?: string, color_temp?: int}|null
     */
    private function supportedChanges(Light $light, array $changes): ?array
    {
        if (($changes['power'] ?? true) === false) {
            return $changes;
        }

        $dropped = false;
        if (isset($changes['color']) && ! $light->supportsColor) {
            unset($changes['color']);
            $dropped = true;
        }
        if (isset($changes['color_temp']) && ! $light->supportsColorTemp) {
            unset($changes['color_temp']);
            $dropped = true;
        }

        if ($dropped && array_diff(array_keys($changes), ['power']) === []) {
            return null;
        }

        return $changes;
    }

    /**
     * Run `$callback` holding the given per-light locks, taken in sorted
     * order so two multi-lamp writers can never deadlock. Callers hold the
     * shared lock already; single-light writes only ever take one lock.
     *
     * @template TReturn
     *
     * @param  list<string>  $keys  per-light lock keys (lightLockKey())
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function withLightLocks(array $keys, Closure $callback): mixed
    {
        $keys = array_values(array_unique($keys));
        sort($keys);

        $deadline = time() + max(0, (int) config('lighting.control_lock_wait', 8));
        $held = [];

        try {
            foreach ($keys as $key) {
                $lock = Cache::lock($key, max(1, (int) config('lighting.control_lock_ttl', 90)));
                try {
                    $lock->block(max(0, $deadline - time()));
                } catch (LockTimeoutException $exception) {
                    throw LightingControlBusy::queueTimeout($exception);
                }
                $held[] = $lock;
            }

            return $callback();
        } finally {
            foreach (array_reverse($held) as $lock) {
                $lock->release();
            }
        }
    }

    private function lightLockKey(string $providerKey, string $id): string
    {
        return self::GROUP_LOCK.':'.$providerKey.':'.$id;
    }

    /** Provider label and exception class only — never messages, secrets or provider internals. */
    private function logWriteFailure(LightProvider $provider, Throwable $e): void
    {
        Log::warning('Lighting write failed', [
            'provider' => $provider->label(),
            'exception' => $e::class,
        ]);
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
