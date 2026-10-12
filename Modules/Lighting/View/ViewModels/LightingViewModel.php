<?php

namespace Modules\Lighting\View\ViewModels;

use Modules\Lighting\Data\Light;
use Modules\Lighting\Data\LightPreset;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Enums\LightMode;
use Modules\Lighting\Models\LightingScene;
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

        $activePreset = $this->activePreset($presets, $snapshot->lights);
        $scenes = LightingScene::query()->ordered()->with('lights')->get();
        $activeSceneId = $activePreset === null ? $this->activeSceneId($scenes->all(), $snapshot->lights) : null;

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
            'activePresetKey' => $activePreset?->key,
            'userScenes' => array_values($scenes->map(fn (LightingScene $scene): array => self::sceneView($scene, $scene->id === $activeSceneId))->all()),
            'scenesUrl' => route('lighting.scenes.store'),
            'sceneUrlTemplate' => url('/lighting/scenes/__SCENE__'),
        ];
    }

    /**
     * View data for one user-scene button (also used, blank, as the JS template).
     *
     * @return array{id: string, name: string, accent: string, subtitle: string, lights: list<array<string, mixed>>, active: bool}
     */
    public static function sceneView(?LightingScene $scene, bool $active = false): array
    {
        $lights = $scene === null ? [] : array_map(static fn (SceneLightState $state): array => $state->toArray(), $scene->states());

        return [
            'id' => $scene === null ? '' : (string) $scene->id,
            'name' => $scene->name ?? '',
            'accent' => $scene->accent ?? '#e0a44e',
            'subtitle' => count($lights).' '.(count($lights) === 1 ? 'lamp' : 'lamps').' · your scene',
            'lights' => $lights,
            'active' => $active,
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

    /**
     * The first user scene whose stored lamps (those still present and
     * reachable) all show their stored state.
     *
     * @param  array<int, LightingScene>  $scenes
     * @param  list<Light>  $lights
     */
    private function activeSceneId(array $scenes, array $lights): ?int
    {
        $byKey = [];
        foreach ($lights as $light) {
            if ($light->reachable) {
                $byKey[$light->provider.'::'.$light->id] = $light;
            }
        }

        foreach ($scenes as $scene) {
            $checked = 0;
            foreach ($scene->states() as $state) {
                $light = $byKey[$state->provider.'::'.$state->id] ?? null;
                if ($light === null) {
                    continue;
                }
                if (! $state->matches($light)) {
                    continue 2;
                }
                $checked++;
            }

            if ($checked > 0) {
                return $scene->id;
            }
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
