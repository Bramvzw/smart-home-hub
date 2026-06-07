<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskColumn;

class MoveTask
{
    public function __construct(
        private readonly ResequenceTasks $resequenceTasks,
    ) {
    }

    public function __invoke(KanbanTask $task, int $columnId, array $orderedTaskIds): KanbanTask
    {
        $column = $task->board->columns()->findOrFail($columnId);
        $task->update([
            'column_id' => $column->id,
            'completed' => $column->isDoneColumn(),
        ]);

        ($this->resequenceTasks)($column, $orderedTaskIds);

        return $task;
    }
}
