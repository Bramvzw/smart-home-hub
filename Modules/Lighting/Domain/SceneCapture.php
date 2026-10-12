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
     * Unreachable lamps are skipped (their state is unknown); an unknown lamp
     * rejects the whole capture. Duplicate targets are captured once.
     *
     * @param  list<array{provider: string, id: string}>  $targets
     * @return list<SceneLightState>
     *
     * @throws UnknownLight
     */
    public function fromSnapshot(LightingSnapshot $snapshot, array $targets): array
    {
        $lights = [];
        foreach ($snapshot->lights as $light) {
            $lights[$light->provider.'::'.$light->id] = $light;
        }

        $states = [];
        foreach ($targets as $target) {
            $key = $target['provider'].'::'.$target['id'];
            $light = $lights[$key] ?? throw UnknownLight::forTarget($target['provider'], $target['id']);

            if ($light->reachable && ! isset($states[$key])) {
                $states[$key] = SceneLightState::capture($light);
            }
        }

        return array_values($states);
    }
}
