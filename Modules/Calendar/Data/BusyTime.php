<?php

namespace Modules\Calendar\Data;

use Carbon\CarbonImmutable;

final readonly class BusyTime
{
    public function __construct(public CarbonImmutable $start, public CarbonImmutable $end) {}
}
