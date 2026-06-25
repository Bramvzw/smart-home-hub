<?php

namespace Modules\Planner\Data;

use Carbon\CarbonImmutable;

final readonly class PlanItemData
{
    public function __construct(
        public ?int $intentionId,
        public string $title,
        public ?string $category,
        public ?CarbonImmutable $start,
        public ?CarbonImmutable $end,
        public string $status = 'proposed',
        public ?string $unplaceableReason = null,
    ) {}

    public function overlaps(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->start && $this->end && $this->start->lt($end) && $this->end->gt($start);
    }
}
