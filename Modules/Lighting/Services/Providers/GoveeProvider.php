<?php

namespace Modules\Lighting\Services\Providers;

use Illuminate\Support\Facades\Cache;
use Modules\Lighting\Contracts\LightProvider;
use Modules\Lighting\Data\Light;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Support\Color;

class GoveeProvider implements LightProvider
{
    /** Kelvin range used when a device does not report its own colorTem range. */
    private const KELVIN_MIN = 2000;

    private const KELVIN_MAX = 9000;

    /** @var array<string, array{model: string, kelvin_min: int, kelvin_max: int}>|null  device id => meta, memoised per request */
    private ?array $devices = null;

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
        $devices = is_array($devices) ? $devices : [];
        $this->devices = $this->metaFromDevices($devices);
        Cache::put($this->metaCacheKey(), $this->devices, (int) config('lighting.govee.model_cache_ttl', 300));

        // One concurrent burst instead of a sequential read per lamp.
        $requests = [];
        foreach ($devices as $device) {
            $requests[] = [
                'device' => (string) ($device['device'] ?? ''),
                'model' => (string) ($device['model'] ?? ''),
            ];
        }
        $states = $this->client->states($requests);

        $lights = [];
        foreach ($devices as $device) {
            $lights[] = $this->map($device, $states[(string) ($device['device'] ?? '')] ?? null);
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

    public function setColorTemp(string $id, int $percent): void
    {
        $this->client->control($id, $this->modelFor($id), 'colorTem', $this->percentToKelvin($percent, ...$this->kelvinRangeFor($id)));
    }

    public function applyState(string $id, bool $power, ?int $brightness, ?string $color, ?int $colorTemp = null): void
    {
        // Govee's API takes one command per call; keep the colour-before-
        // brightness order and skip the state reads presets don't need.
        if (! $power) {
            $this->setPower($id, false);

            return;
        }

        $this->setPower($id, true);
        if ($colorTemp !== null) {
            $this->setColorTemp($id, $colorTemp);
        } elseif ($color !== null) {
            $this->setColor($id, $color);
        }
        if ($brightness !== null) {
            $this->setBrightness($id, $brightness);
        }
    }

    private function map(array $device, ?array $state): Light
    {
        $id = (string) ($device['device'] ?? '');
        $supportCmds = (array) ($device['supportCmds'] ?? []);
        $supportsColor = in_array('color', $supportCmds, true);
        $supportsColorTemp = in_array('colorTem', $supportCmds, true);
        [$kelvinMin, $kelvinMax] = $this->kelvinRange($device);
        $mode = $supportsColor ? LightMode::Colour : LightMode::White;
        $colorTemp = null;

        // Per-device isolation: a missing state read marks only this light unreachable.
        if ($state !== null) {
            $props = $this->flatten($state['properties'] ?? []);
            $reachable = (bool) ($props['online'] ?? false) && (bool) ($device['controllable'] ?? false);
            $on = ($props['powerState'] ?? null) === 'on';
            $brightness = (int) ($props['brightness'] ?? 0);
            $color = isset($props['color']['r'])
                ? Color::rgbToHex((int) $props['color']['r'], (int) $props['color']['g'], (int) $props['color']['b'])
                : null;

            // Govee reports a non-zero Kelvin value only while the white LEDs are active.
            $kelvin = (int) ($props['colorTemInKelvin'] ?? $props['colorTem'] ?? 0);
            if ($supportsColorTemp && $kelvin > 0) {
                $mode = LightMode::White;
                $colorTemp = $this->kelvinToPercent($kelvin, $kelvinMin, $kelvinMax);
            }
        } else {
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
            mode: $mode,
            colorTemp: $colorTemp,
            supportsColorTemp: $supportsColorTemp,
        );
    }

    private function modelFor(string $id): string
    {
        return $this->metaFor($id)['model'] ?? '';
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function kelvinRangeFor(string $id): array
    {
        $meta = $this->metaFor($id);

        return [$meta['kelvin_min'] ?? self::KELVIN_MIN, $meta['kelvin_max'] ?? self::KELVIN_MAX];
    }

    /**
     * @return array{model: string, kelvin_min: int, kelvin_max: int}|null
     */
    private function metaFor(string $id): ?array
    {
        if ($this->devices === null) {
            $cached = Cache::get($this->metaCacheKey(), []);

            if (is_array($cached) && isset($cached[$id])) {
                $this->devices = $cached;

                return $this->devices[$id];
            }

            $devices = (array) ($this->client->devices()['devices'] ?? []);
            $this->devices = $this->metaFromDevices($devices);
            Cache::put($this->metaCacheKey(), $this->devices, (int) config('lighting.govee.model_cache_ttl', 300));
        }

        return $this->devices[$id] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $devices
     * @return array<string, array{model: string, kelvin_min: int, kelvin_max: int}>
     */
    private function metaFromDevices(array $devices): array
    {
        $meta = [];
        foreach ($devices as $device) {
            [$kelvinMin, $kelvinMax] = $this->kelvinRange($device);
            $meta[(string) ($device['device'] ?? '')] = [
                'model' => (string) ($device['model'] ?? ''),
                'kelvin_min' => $kelvinMin,
                'kelvin_max' => $kelvinMax,
            ];
        }

        return $meta;
    }

    /**
     * The device's supported Kelvin range from properties.colorTem.range, or
     * the generic fallback when it is missing or nonsensical.
     *
     * @param  array<string, mixed>  $device
     * @return array{0: int, 1: int}
     */
    private function kelvinRange(array $device): array
    {
        $range = $device['properties']['colorTem']['range'] ?? null;
        $min = is_array($range) && is_numeric($range['min'] ?? null) ? (int) $range['min'] : self::KELVIN_MIN;
        $max = is_array($range) && is_numeric($range['max'] ?? null) ? (int) $range['max'] : self::KELVIN_MAX;

        return $min > 0 && $max > $min ? [$min, $max] : [self::KELVIN_MIN, self::KELVIN_MAX];
    }

    /** 0 (warm) - 100 (cool) linearly onto the device's Kelvin range. */
    private function percentToKelvin(int $percent, int $min, int $max): int
    {
        return (int) round($min + (max(0, min(100, $percent)) / 100) * ($max - $min));
    }

    private function kelvinToPercent(int $kelvin, int $min, int $max): int
    {
        return max(0, min(100, (int) round((($kelvin - $min) / ($max - $min)) * 100)));
    }

    private function metaCacheKey(): string
    {
        return 'lighting:govee:devices';
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
