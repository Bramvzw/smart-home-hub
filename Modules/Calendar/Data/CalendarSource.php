<?php

namespace Modules\Calendar\Data;

final readonly class CalendarSource
{
    public function __construct(
        public string $label,
        public string $color,
        public string $url,
    ) {}
}
