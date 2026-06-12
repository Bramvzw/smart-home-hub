<?php

namespace Modules\Calendar\Data;

final readonly class CalendarFeed
{
    /**
     * @param  list<CalendarEvent>  $events
     * @param  list<string>  $staleFeeds  Labels of feeds served from stale cache.
     */
    public function __construct(
        public array $events,
        public bool $stale = false,
        public bool $failed = false,
        public array $staleFeeds = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->events === [];
    }
}
