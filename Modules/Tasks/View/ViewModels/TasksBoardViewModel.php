<?php

namespace Modules\Tasks\View\ViewModels;

use Illuminate\Support\Collection;
use Modules\Tasks\Http\Resources\TaskBoardStateResource;
use Modules\Tasks\Models\TaskBoard;

class TasksBoardViewModel
{
    public function state(TaskBoard $activeBoard): array
    {
        $boards = $this->boards();

        $activeBoard->load([
            'labels',
            'columns.tasks.labels',
            'columns.tasks.checklistItems',
            'columns.tasks.recurrence',
        ]);

        return TaskBoardStateResource::make([
            'boards' => $boards,
            'activeBoard' => $activeBoard,
        ])->resolve();
    }

    private function boards(): Collection
    {
        return TaskBoard::query()
            ->withCount(['tasks as active_tasks_count' => fn ($query) => $query->active()])
            ->orderBy('position')
            ->get();
    }
}
