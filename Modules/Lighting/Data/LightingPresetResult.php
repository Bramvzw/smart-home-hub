<?php

namespace Modules\Lighting\Data;

final readonly class LightingPresetResult
{
    /**
     * @param  list<string>  $failedLights
     */
    public function __construct(
        public LightPreset $preset,
        public int $applied,
        public int $skipped,
        public array $failedLights = [],
    ) {}

    /**
     * @return array{preset: array<string, mixed>, applied: int, skipped: int, failed_lights: list<string>}
     */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset->toArray(),
            'applied' => $this->applied,
            'skipped' => $this->skipped,
            'failed_lights' => $this->failedLights,
        ];
    }
}
