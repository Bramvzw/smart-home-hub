<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Cache;
use Modules\Lighting\Contracts\LightProvider;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Support\Color;
use RuntimeException;

class GoveeProvider implements LightProvider
{
    /** @var array<string, string>|null  device id => model, memoised per request */
    private ?array $models = null;

    public function __construct(
        private readonly GoveeApiClient $client,
    ) {}

    public function key(): string
    {
        return 'govee';
    }

    public function label(): string
    {
        return 'Govee';
    }

    public function isConfigured(): bool
    {
        return ! empty(config('lighting.govee.api_key'));
    }

    public function lights(): array
    {
        $devices = $this->client->devices()['devices'] ?? [];
        $this->models = $this->modelsFromDevices(is_array($devices) ? $devices : []);
        Cache::put($this->modelCacheKey(), $this->models, (int) config('lighting.govee.model_cache_ttl', 300));

        $lights = [];
        foreach (is_array($devices) ? $devices : [] as $device) {
            $lights[] = $this->map($device);
        }

        return $lights;
    }

    public function setPower(string $id, bool $on): void
    {
        $this->client->control($id, $this->modelFor($id), 'turn', $on ? 'on' : 'off');
    }

    public function setBrightness(string $id, int $percent): void
    {
        $this->client->control($id, $this->modelFor($id), 'brightness', max(0, min(100, $percent)));
    }

    public function setColor(string $id, string $hex): void
    {
        [$r, $g, $b] = Color::hexToRgb($hex);
        $this->client->control($id, $this->modelFor($id), 'color', ['r' => $r, 'g' => $g, 'b' => $b]);
    }

    private function map(array $device): Light
    {
        $id = (string) ($device['device'] ?? '');
        $supportCmds = (array) ($device['supportCmds'] ?? []);
        $supportsColor = in_array('color', $supportCmds, true);

        // Per-device isolation: a failing state read marks only this light unreachable.
        try {
            $props = $this->flatten($this->client->state($id, (string) ($device['model'] ?? ''))['properties'] ?? []);
            $reachable = (bool) ($props['online'] ?? false) && (bool) ($device['controllable'] ?? false);
            $on = ($props['powerState'] ?? null) === 'on';
            $brightness = (int) ($props['brightness'] ?? 0);
            $color = isset($props['color']['r'])
                ? Color::rgbToHex((int) $props['color']['r'], (int) $props['color']['g'], (int) $props['color']['b'])
                : null;
        } catch (RuntimeException) {
            $reachable = false;
            $on = false;
            $brightness = 0;
            $color = null;
        }

        return new Light(
            provider: $this->key(),
            id: $id,
            name: (string) ($device['deviceName'] ?? 'Govee'),
            on: $on,
            brightness: max(0, min(100, $brightness)),
            color: $color,
            reachable: $reachable,
            supportsColor: $supportsColor,
        );
    }

    private function modelFor(string $id): string
    {
        if ($this->models === null) {
            $cached = Cache::get($this->modelCacheKey(), []);

            if (is_array($cached) && isset($cached[$id])) {
                $this->models = $cached;

                return (string) $this->models[$id];
            }

            $devices = (array) ($this->client->devices()['devices'] ?? []);
            $this->models = $this->modelsFromDevices($devices);
            Cache::put($this->modelCacheKey(), $this->models, (int) config('lighting.govee.model_cache_ttl', 300));
        }

        return $this->models[$id] ?? '';
    }

    /**
     * @param  array<int, array<string, mixed>>  $devices
     * @return array<string, string>
     */
    private function modelsFromDevices(array $devices): array
    {
        $models = [];
        foreach ($devices as $device) {
            $models[(string) ($device['device'] ?? '')] = (string) ($device['model'] ?? '');
        }

        return $models;
    }

    private function modelCacheKey(): string
    {
        return 'lighting:govee:models';
    }

    /**
     * Govee returns properties as a list of single-key maps; flatten to one map.
     *
     * @param  array<int, array<string, mixed>>  $properties
     * @return array<string, mixed>
     */
    private function flatten(array $properties): array
    {
        $flat = [];
        foreach ($properties as $entry) {
            if (is_array($entry)) {
                $flat += $entry;
            }
        }

        return $flat;
    }
}
