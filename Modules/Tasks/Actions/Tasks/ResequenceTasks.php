<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Models\TaskColumn;

class ResequenceTasks
{
    public function __invoke(TaskColumn $column, array $orderedIds): void
    {
        collect($orderedIds)->values()->each(function (int $taskId, int $position) use ($column): void {
            $column->tasks()->whereKey($taskId)->update(['position' => $position]);
        });
    }
}
