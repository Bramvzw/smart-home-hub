<?php

namespace Modules\Lighting\Actions;

use Modules\Lighting\Data\Light;
use Modules\Lighting\Services\LightingService;

class ControlLight
{
    public function __construct(
        private readonly LightingService $service,
    ) {}

    /**
     * @param  array{power?: bool, brightness?: int, color?: string}  $changes
     */
    public function __invoke(string $provider, string $id, array $changes): Light
    {
        return $this->service->control($provider, $id, $changes);
    }
}
