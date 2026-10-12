<?php

namespace Modules\Lighting\Data;

final readonly class LightingGroupResult
{
    /**
     * @param  list<string>  $failed  Names of lights (or provider labels) that could not be updated.
     * @param  list<string>  $skipped  Names of lights left untouched because they cannot show the requested colour or colour temperature.
     * @param  list<array{provider: string, id: string}>  $missingLights  Stored scene lamps that no longer exist or whose provider is not configured.
     * @param  list<array{provider: string, id: string, name: string}>  $failedLights  Every lamp that was not updated; a provider-wide failure lists each targeted lamp of that provider (named after the provider).
     * @param  list<array{provider: string, id: string, name: string}>  $skippedLights  The lamps behind `$skipped`.
     */
    public function __construct(
        public int $applied,
        public array $failed = [],
        public array $skipped = [],
        public array $missingLights = [],
        public array $failedLights = [],
        public array $skippedLights = [],
    ) {}

    /** Nothing was applied although at least one light was targeted. */
    public function failedCompletely(): bool
    {
        return $this->applied === 0 && $this->failed !== [];
    }

    /**
     * @return array{applied: int, failed: list<string>, skipped: list<string>, failed_lights: list<array{provider: string, id: string, name: string}>, skipped_lights: list<array{provider: string, id: string, name: string}>, missing_lights: list<array{provider: string, id: string}>}
     */
    public function toArray(): array
    {
        return [
            'applied' => $this->applied,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'failed_lights' => $this->failedLights,
            'skipped_lights' => $this->skippedLights,
            'missing_lights' => $this->missingLights,
        ];
    }
}
