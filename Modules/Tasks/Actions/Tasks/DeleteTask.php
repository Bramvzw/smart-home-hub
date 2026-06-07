<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;

class DeleteTask
{
    public function __invoke(KanbanTask $task): TaskBoard
    {
        $board = $task->board;
        $task->delete();

        return $board;
    }
}
