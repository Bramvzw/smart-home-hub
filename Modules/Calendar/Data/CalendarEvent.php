<?php

namespace Modules\Calendar\Data;

use Carbon\CarbonImmutable;

final readonly class CalendarEvent
{
    public function __construct(
        public string $uid,
        public string $summary,
        public CarbonImmutable $start,
        public CarbonImmutable $end,
        public bool $allDay,
        public ?string $location = null,
    ) {}

    public function toArray(): array
    {
        return [
            'uid' => $this->uid,
            'summary' => $this->summary,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'all_day' => $this->allDay,
            'location' => $this->location,
        ];
    }
}
