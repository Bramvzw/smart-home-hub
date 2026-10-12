<?php

namespace Modules\Lighting\Actions\Scenes;

use Modules\Lighting\Data\LightingGroupResult;
use Modules\Lighting\Models\LightingScene;
use Modules\Lighting\Services\LightingService;

class ApplyLightingScene
{
    public function __construct(
        private readonly LightingService $service,
    ) {}

    /** Only the lamps stored in the scene change; unreachable ones are skipped and reported. */
    public function __invoke(LightingScene $scene): LightingGroupResult
    {
        $scene->loadMissing('lights');

        return $this->service->applyLightStates($scene->states());
    }
}
