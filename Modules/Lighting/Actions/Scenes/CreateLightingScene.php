<?php

namespace Modules\Lighting\Actions\Scenes;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Lighting\Domain\SceneCapture;
use Modules\Lighting\Exceptions\UnknownLight;
use Modules\Lighting\Models\LightingScene;
use Modules\Lighting\Models\LightingSceneLight;
use Modules\Lighting\Services\LightingService;

class CreateLightingScene
{
    public function __construct(
        private readonly LightingService $service,
        private readonly SceneCapture $capture,
    ) {}

    /**
     * Save a scene from the current state of the given lamps.
     *
     * @param  list<array{provider: string, id: string}>  $lights
     *
     * @throws UnknownLight
     * @throws ValidationException when none of the lamps is reachable
     */
    public function __invoke(string $name, string $accent, array $lights): LightingScene
    {
        $states = $this->capture->fromSnapshot($this->service->snapshot(), $lights);

        if ($states === []) {
            throw ValidationException::withMessages(['lights' => 'None of the selected lamps is reachable right now.']);
        }

        return DB::transaction(function () use ($name, $accent, $states): LightingScene {
            $scene = LightingScene::query()->create([
                'name' => $name,
                'accent' => strtolower($accent),
                'sort_order' => ((int) LightingScene::query()->max('sort_order')) + 1,
            ]);

            foreach ($states as $state) {
                $scene->lights()->create(LightingSceneLight::attributesFrom($state));
            }

            return $scene->load('lights');
        });
    }
}
