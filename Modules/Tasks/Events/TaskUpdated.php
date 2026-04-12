<?php

namespace Modules\Tasks\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Tasks\Models\Task;

class TaskUpdated
{
    use Dispatchable;

    public function __construct(public Task $task) {}
}
