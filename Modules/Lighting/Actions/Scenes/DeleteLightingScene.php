<?php

namespace Modules\Lighting\Actions\Scenes;

use Modules\Lighting\Models\LightingScene;

class DeleteLightingScene
{
    public function __invoke(LightingScene $scene): void
    {
        // Stored lamp states go with it (cascade).
        $scene->delete();
    }
}
