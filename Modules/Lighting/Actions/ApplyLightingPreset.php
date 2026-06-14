<?php

namespace Modules\Lighting\Actions;

use Modules\Lighting\Data\LightingPresetResult;
use Modules\Lighting\Services\LightingService;

class ApplyLightingPreset
{
    public function __construct(
        private readonly LightingService $service,
    ) {}

    public function __invoke(string $preset): LightingPresetResult
    {
        return $this->service->applyPreset($preset);
    }
}
