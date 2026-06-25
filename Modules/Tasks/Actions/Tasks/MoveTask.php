<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Actions\Recurrences\CompleteMaintenanceCard;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskColumn;

class MoveTask
{
    public function __construct(
        private readonly ResequenceTasks $resequenceTasks,
        private readonly CompleteMaintenanceCard $completeMaintenanceCard,
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
        ($this->completeMaintenanceCard)($task);

        return $task;
    }
}
