<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Models\KanbanTask;

class ArchiveTask
{
    public function __invoke(KanbanTask $task): KanbanTask
    {
        $task->update(['archived_at' => $task->archived_at ? null : now()]);

        return $task;
    }
}
