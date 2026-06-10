<?php

namespace Modules\Calendar\Data;

final readonly class CalendarFeed
{
    /**
     * @param  list<CalendarEvent>  $events
     */
    public function __construct(
        public array $events,
        public bool $stale = false,
        public bool $failed = false,
    ) {}

    public function isEmpty(): bool
    {
        return $this->events === [];
    }
}
