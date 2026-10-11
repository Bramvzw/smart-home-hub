<?php

namespace Modules\Lighting\Actions;

use Modules\Lighting\Data\LightingGroupResult;
use Modules\Lighting\Services\LightingService;

class ControlLights
{
    public function __construct(
        private readonly LightingService $service,
    ) {}

    /**
     * @param  list<array{provider: string, id: string}>  $lights
     * @param  array{power?: bool, brightness?: int, color?: string, color_temp?: int}  $changes
     */
    public function __invoke(array $lights, array $changes): LightingGroupResult
    {
        return $this->service->controlLights($lights, $changes);
    }
}
