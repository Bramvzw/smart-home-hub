<?php

namespace Modules\Lighting\Data;

final readonly class LightingGroupResult
{
    /**
     * @param  list<string>  $failed  Names of lights (or provider labels) that could not be updated.
     */
    public function __construct(
        public int $applied,
        public array $failed = [],
    ) {}

    /** Nothing was applied although at least one light was targeted. */
    public function failedCompletely(): bool
    {
        return $this->applied === 0 && $this->failed !== [];
    }

    /**
     * @return array{applied: int, failed: list<string>}
     */
    public function toArray(): array
    {
        return [
            'applied' => $this->applied,
            'failed' => $this->failed,
        ];
    }
}
