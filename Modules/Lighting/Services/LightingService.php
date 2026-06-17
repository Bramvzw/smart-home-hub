<?php

namespace Modules\Lighting\Services;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Lighting\Contracts\LightProvider;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Data\LightingPresetResult;
use Modules\Lighting\Data\LightingSnapshot;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Exceptions\LightingControlBusy;
use Modules\Lighting\Exceptions\UnknownLightingPreset;
use Modules\Lighting\Services\Providers\GoveeProvider;
use Modules\Lighting\Services\Providers\TuyaProvider;
use RuntimeException;
use Throwable;

class LightingService
{
    /** @var list<LightProvider> */
    private array $providers;

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
     * Apply changes to one light and return its refreshed state.
     *
     * @param  array{power?: bool, brightness?: int, color?: string}  $changes
     */
    public function control(string $providerKey, string $id, array $changes): Light
    {
        return $this->withControlLock(function () use ($providerKey, $id, $changes): Light {
            $provider = $this->providerByKey($providerKey);

            if (array_key_exists('power', $changes)) {
                $provider->setPower($id, (bool) $changes['power']);
            }
            if (array_key_exists('brightness', $changes)) {
                $provider->setBrightness($id, (int) $changes['brightness']);
            }
            if (array_key_exists('color', $changes)) {
                $provider->setColor($id, (string) $changes['color']);
            }

            Cache::forget($this->cacheKey($provider));

            // Optimistic return — re-fetching per action caused visible lag; the page reloads fresh state.
            return new Light(
                provider: $providerKey,
                id: $id,
                name: '',
                on: (bool) ($changes['power'] ?? true),
                brightness: (int) ($changes['brightness'] ?? 0),
                color: isset($changes['color']) ? (string) $changes['color'] : null,
                reachable: true,
                supportsColor: array_key_exists('color', $changes),
            );
        });
    }

    public function applyPreset(string $key): LightingPresetResult
    {
        return $this->withControlLock(function () use ($key): LightingPresetResult {
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

                foreach ($lights as $light) {
                    if (! $light->reachable) {
                        $skipped++;

                        continue;
                    }

                    try {
                        $this->applyPresetToLight($provider, $light, $preset);
                        $applied++;
                    } catch (Throwable) {
                        $failed[] = $light->name;
                    }
                }

                Cache::forget($this->cacheKey($provider));
            }

            return new LightingPresetResult($preset, $applied, $skipped, array_values(array_unique($failed)));
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
        return array_values(array_filter($this->providers, static fn (LightProvider $p) => $p->isConfigured()));
    }

    /**
     * @return list<Light>
     */
    private function cachedLights(LightProvider $provider): array
    {
        return Cache::remember(
            $this->cacheKey($provider),
            (int) config('lighting.cache_ttl', 30),
            static fn () => $provider->lights(),
        );
    }

    private function applyPresetToLight(LightProvider $provider, Light $light, LightPreset $preset): void
    {
        if (! $preset->power) {
            if ($light->on) {
                $provider->setPower($light->id, false);
            }

            return;
        }

        if (! $light->on) {
            $provider->setPower($light->id, true);
        }

        if ($preset->brightness !== null && $light->brightness !== $preset->brightness) {
            $provider->setBrightness($light->id, $preset->brightness);
        }

        if ($preset->color !== null && $light->supportsColor && $this->normaliseHex($light->color) !== $this->normaliseHex($preset->color)) {
            $provider->setColor($light->id, $preset->color);
        }
    }

    /**
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    private function withControlLock(Closure $callback): mixed
    {
        try {
            return Cache::lock(
                'lighting:control',
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
        );
    }

    private function normaliseHex(?string $hex): ?string
    {
        if ($hex === null || trim($hex) === '') {
            return null;
        }

        $hex = strtolower(trim($hex));

        return str_starts_with($hex, '#') ? $hex : '#'.$hex;
    }

    private function providerByKey(string $key): LightProvider
    {
        foreach ($this->configuredProviders() as $provider) {
            if ($provider->key() === $key) {
                return $provider;
            }
        }

        throw new RuntimeException('Unknown or unconfigured lighting provider.');
    }

    private function cacheKey(LightProvider $provider): string
    {
        return 'lighting:lights:'.$provider->key();
    }
}
