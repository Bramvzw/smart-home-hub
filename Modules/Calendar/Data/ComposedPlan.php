<?php

namespace Modules\Calendar\Data;

final readonly class ComposedPlan
{
    /**
     * @param  list<PlanItemData>  $items
     */
    public function __construct(public string $summary, public array $items, public bool $isFallback = false) {}
}
