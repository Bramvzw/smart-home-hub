<?php

namespace Modules\Lighting\View\ViewModels;

use Modules\Lighting\Data\Light;
use Modules\Lighting\Services\LightingService;

class LightingViewModel
{
    public function __construct(
        private readonly LightingService $service,
    ) {}

    public function page(): array
    {
        $snapshot = $this->service->snapshot();

        return [
            'lightsByProvider' => $this->groupByProvider($snapshot->lights),
            'providerLabels' => $this->providerLabels(),
            'unreachableProviders' => $snapshot->unreachableProviders,
            'configured' => $this->service->isConfigured(),
            'controlUrlTemplate' => url('/lighting/lights/__PROVIDER__/__ID__'),
            'presetUrlTemplate' => url('/lighting/presets/__PRESET__'),
            'presets' => $this->service->presets(),
        ];
    }

    /**
     * @param  list<Light>  $lights
     * @return array<string, list<Light>>
     */
    private function groupByProvider(array $lights): array
    {
        $grouped = [];
        foreach ($lights as $light) {
            $grouped[$light->provider][] = $light;
        }

        return $grouped;
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
