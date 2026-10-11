<?php

namespace Modules\Lighting\View\ViewModels;

use Modules\Lighting\Data\Light;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Services\LightingService;

class LightingViewModel
{
    /** Scene-strip subtitles for the built-in presets; others get a computed one. */
    private const PRESET_SUBTITLES = [
        'bright' => 'Cool white · 100%',
        'cozy' => 'Warm white · 70%',
        'movie' => 'Blue · 42%',
        'night' => 'Warm white · 1%',
        'night_light' => 'Strip only · 1%',
    ];

    public function __construct(
        private readonly LightingService $service,
    ) {}

    public function page(): array
    {
        $snapshot = $this->service->snapshot();
        $labels = $this->providerLabels();
        $presets = $this->service->presets();

        $tiles = array_map(fn (Light $light): array => [
            'light' => $light,
            'key' => $light->provider.'::'.$light->id,
            'providerLabel' => $labels[$light->provider] ?? ucfirst($light->provider),
            'stateLabel' => self::stateLabel($light),
            'url' => str_replace(['__PROVIDER__', '__ID__'], [$light->provider, rawurlencode($light->id)], url('/lighting/lights/__PROVIDER__/__ID__')),
        ], $snapshot->lights);

        $offPreset = null;
        $scenePresets = [];
        foreach ($presets as $preset) {
            // "All off" lives in the header only, not in the scene strip.
            if (! $preset->power && $preset->targetNameContains === []) {
                $offPreset ??= $preset;

                continue;
            }

            $scenePresets[] = ['preset' => $preset, 'subtitle' => $this->presetSubtitle($preset)];
        }

        return [
            'tiles' => $tiles,
            'totalLights' => count($snapshot->lights),
            'onCount' => count(array_filter($snapshot->lights, static fn (Light $light): bool => $light->reachable && $light->on)),
            'unreachableProviders' => $snapshot->unreachableProviders,
            'configured' => $this->service->isConfigured(),
            'presetUrlTemplate' => url('/lighting/presets/__PRESET__'),
            'groupUrl' => route('lighting.lights.update-many'),
            'stateUrl' => route('lighting.state'),
            'pollSeconds' => max(0, (int) config('lighting.poll_seconds', 120)),
            'offPreset' => $offPreset,
            'scenePresets' => $scenePresets,
            'activePresetKey' => $this->activePreset($presets, $snapshot->lights)?->key,
        ];
    }

    /** Tile state line: `70% · Warm white`, `40% · Colour`, `Off`, `Offline`. */
    public static function stateLabel(Light $light): string
    {
        if (! $light->reachable) {
            return 'Offline';
        }

        if (! $light->on) {
            return 'Off';
        }

        if ($light->mode === LightMode::Colour) {
            return $light->brightness.'% · Colour';
        }

        return $light->brightness.'% · '.self::whiteName($light->colorTemp);
    }

    private static function whiteName(?int $colorTemp): string
    {
        return match (true) {
            $colorTemp === null => 'White',
            $colorTemp < 34 => 'Warm white',
            $colorTemp < 67 => 'Neutral white',
            default => 'Cool white',
        };
    }

    private function presetSubtitle(LightPreset $preset): string
    {
        if (isset(self::PRESET_SUBTITLES[$preset->key])) {
            return self::PRESET_SUBTITLES[$preset->key];
        }

        $kind = match (true) {
            $preset->colorTemp !== null => self::whiteName($preset->colorTemp),
            $preset->color !== null => 'Colour',
            default => 'Preset',
        };

        return $preset->brightness === null ? $kind : $kind.' · '.$preset->brightness.'%';
    }

    /**
     * The first preset whose target state every reachable targeted lamp is in.
     *
     * @param  list<LightPreset>  $presets
     * @param  list<Light>  $lights
     */
    private function activePreset(array $presets, array $lights): ?LightPreset
    {
        $reachable = array_values(array_filter($lights, static fn (Light $light): bool => $light->reachable));

        foreach ($presets as $preset) {
            $targeted = array_filter($reachable, $preset->targetsLight(...));

            if ($targeted === []) {
                continue;
            }

            foreach ($targeted as $light) {
                if (! $this->presetMatches($preset, $light)) {
                    continue 2;
                }
            }

            return $preset;
        }

        return null;
    }

    private function presetMatches(LightPreset $preset, Light $light): bool
    {
        if (! $preset->power) {
            return ! $light->on;
        }

        if (! $light->on) {
            return false;
        }

        if ($preset->brightness !== null && $light->brightness !== $preset->brightness) {
            return false;
        }

        if ($preset->colorTemp !== null && $light->supportsColorTemp) {
            return $light->mode === LightMode::White && $light->colorTemp === $preset->colorTemp;
        }

        if ($preset->color !== null && $light->supportsColor) {
            return strtolower(ltrim((string) $light->color, '#')) === strtolower(ltrim($preset->color, '#'));
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function providerLabels(): array
    {
        $labels = [];
        foreach ($this->service->configuredProviders() as $provider) {
            $labels[$provider->key()] = $provider->label();
        }

        return $labels;
    }
}
