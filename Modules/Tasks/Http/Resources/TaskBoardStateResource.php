<?php

namespace Modules\Tasks\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskChecklistItem;
use Modules\Tasks\Models\TaskColumn;
use Modules\Tasks\Models\TaskLabel;

class TaskBoardStateResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        /** @var TaskBoard $activeBoard */
        $activeBoard = $this->resource['activeBoard'];

        return [
            'boards' => $this->resource['boards']->map(fn (TaskBoard $board): array => [
                'id' => $board->id,
                'name' => $board->name,
                'count' => $board->active_tasks_count,
            ])->values(),
            'activeBoardId' => $activeBoard->id,
            'board' => [
                'id' => $activeBoard->id,
                'name' => $activeBoard->name,
                'labels' => $activeBoard->labels->map(fn (TaskLabel $label): array => [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                ])->values(),
                'columns' => $activeBoard->columns->map(fn (TaskColumn $column): array => [
                    'id' => $column->id,
                    'name' => $column->name,
                    'tasks' => $column->tasks->map(fn (KanbanTask $task): array => [
                        'id' => $task->id,
                        'title' => $task->title,
                        'description' => $task->description ?? '',
                        'priority' => $task->priority,
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'completed' => $task->completed,
                        'archived' => $task->archived_at !== null,
                        'created_at' => $task->created_at?->format('Y-m-d'),
                        'labels' => $task->labels->map(fn (TaskLabel $label): array => [
                            'id' => $label->id,
                            'name' => $label->name,
                            'color' => $label->color,
                        ])->values(),
                        'checklist' => $task->checklistItems->map(fn (TaskChecklistItem $item): array => [
                            'id' => $item->id,
                            'text' => $item->text,
                            'completed' => $item->completed,
                        ])->values(),
                    ])->values(),
                ])->values(),
            ],
        ];
    }
}
