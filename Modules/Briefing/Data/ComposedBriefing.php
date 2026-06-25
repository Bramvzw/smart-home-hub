<?php

namespace Modules\Briefing\Data;

final readonly class ComposedBriefing
{
    public function __construct(
        public string $body,
        public ?string $model,
        public bool $isFallback,
    ) {}
}
