<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;

class CreateTask
{
    public function __invoke(TaskBoard $board, int $columnId, string $title): KanbanTask
    {
        $column = $board->columns()->findOrFail($columnId);

        return $board->tasks()->create([
            'column_id' => $column->id,
            'title' => $title,
            'description' => '',
            'priority' => 'normal',
            'completed' => $column->isDoneColumn(),
            'position' => (int) $column->tasks()->max('position') + 1,
        ]);
    }
}
