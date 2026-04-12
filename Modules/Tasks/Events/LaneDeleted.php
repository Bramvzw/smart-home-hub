<?php

namespace Modules\Tasks\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Tasks\Models\Lane;

class LaneDeleted
{
    use Dispatchable;

    public function __construct(public Lane $lane) {}
}
