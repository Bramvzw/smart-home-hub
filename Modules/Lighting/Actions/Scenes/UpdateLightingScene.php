<?php

namespace Modules\Lighting\Actions\Scenes;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Lighting\Domain\SceneCapture;
use Modules\Lighting\Exceptions\UnknownLight;
use Modules\Lighting\Models\LightingScene;
use Modules\Lighting\Models\LightingSceneLight;
use Modules\Lighting\Services\LightingService;

class UpdateLightingScene
{
    public function __construct(
        private readonly LightingService $service,
        private readonly SceneCapture $capture,
    ) {}

    /**
     * Rename / recolour a scene, and re-capture its lamps when given ("Update
     * to current"). Re-capturing replaces the stored state of every reachable
     * lamp; a lamp that is offline right now keeps its stored state.
     *
     * @param  list<array{provider: string, id: string}>|null  $lights
     *
     * @throws UnknownLight
     * @throws ValidationException when none of the lamps is reachable
     */
    public function __invoke(LightingScene $scene, ?string $name = null, ?string $accent = null, ?array $lights = null): LightingScene
    {
        $states = null;
        if ($lights !== null) {
            $snapshot = $this->service->snapshot();

            if ($this->capture->fromSnapshot($snapshot, $lights) === []) {
                throw ValidationException::withMessages(['lights' => 'None of the selected lamps is reachable right now.']);
            }

            $scene->loadMissing('lights');
            $states = $this->capture->fromSnapshot($snapshot, $lights, $scene->states());
        }

        return DB::transaction(function () use ($scene, $name, $accent, $states): LightingScene {
            $scene->fill(array_filter([
                'name' => $name,
                'accent' => $accent === null ? null : strtolower($accent),
            ], static fn (?string $value): bool => $value !== null));
            $scene->save();

            if ($states !== null) {
                $scene->lights()->delete();
                foreach ($states as $state) {
                    $scene->lights()->create(LightingSceneLight::attributesFrom($state));
                }
            }

            return $scene->load('lights');
        });
    }
}
