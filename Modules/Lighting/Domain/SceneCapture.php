<?php

namespace Modules\Lighting\Domain;

use Modules\Lighting\Data\LightingSnapshot;
use Modules\Lighting\Data\SceneLightState;
use Modules\Lighting\Exceptions\UnknownLight;

/**
 * Turns "these lamps" into stored scene states, using a light snapshot.
 */
final class SceneCapture
{
    /**
     * Unreachable lamps are skipped (their state is unknown) unless a
     * previously stored state is given for them, which is then kept (re-capture
     * of a scene with an offline lamp). An unknown lamp rejects the whole
     * capture. Duplicate targets are captured once.
     *
     * @param  list<array{provider: string, id: string}>  $targets
     * @param  list<SceneLightState>  $previous
     * @return list<SceneLightState>
     *
     * @throws UnknownLight
     */
    public function fromSnapshot(LightingSnapshot $snapshot, array $targets, array $previous = []): array
    {
        $stored = [];
        foreach ($previous as $state) {
            $stored[$state->provider.'::'.$state->id] = $state;
        }

        $lights = [];
        foreach ($snapshot->lights as $light) {
            $lights[$light->provider.'::'.$light->id] = $light;
        }

        $states = [];
        foreach ($targets as $target) {
            $key = $target['provider'].'::'.$target['id'];
            $light = $lights[$key] ?? throw UnknownLight::forTarget($target['provider'], $target['id']);

            if (isset($states[$key])) {
                continue;
            }
            if ($light->reachable) {
                $states[$key] = SceneLightState::capture($light);
            } elseif (isset($stored[$key])) {
                $states[$key] = $stored[$key];
            }
        }

        return array_values($states);
    }
}
