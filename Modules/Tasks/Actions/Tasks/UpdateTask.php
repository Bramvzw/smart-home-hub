<?php

namespace Modules\Tasks\Actions\Tasks;

use Modules\Tasks\Actions\Recurrences\CompleteMaintenanceCard;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskColumn;

class UpdateTask
{
    public function __construct(
        private readonly CompleteMaintenanceCard $completeMaintenanceCard,
    ) {
    }

    public function __invoke(KanbanTask $task, array $data): KanbanTask
    {
        $completedExplicitly = array_key_exists('completed', $data) && (bool) $data['completed'];

        $task->fill(collect($data)->only(['title', 'description', 'priority', 'completed'])->all());
        $task->due_date = array_key_exists('due_date', $data) ? $data['due_date'] : $task->due_date;

        if (array_key_exists('completed', $data)) {
            $this->syncColumnForCompletedState($task, (bool) $data['completed']);
        }

        $task->save();

        if (array_key_exists('labels', $data)) {
            $this->syncLabels($task, $data['labels']);
        }

        if (array_key_exists('checklist', $data)) {
            $this->syncChecklist($task, $data['checklist']);
        }

        if ($completedExplicitly) {
            ($this->completeMaintenanceCard)($task);
        }

        return $task;
    }

    private function syncColumnForCompletedState(KanbanTask $task, bool $completed): void
    {
        $doneColumn = $task->board->columns->first(fn (TaskColumn $column) => $column->isDoneColumn());

        if ($doneColumn && $completed) {
            $task->column_id = $doneColumn->id;

            return;
        }

        if ($doneColumn && $task->column_id === $doneColumn->id) {
            $fallbackColumn = $task->board->columns->first(fn (TaskColumn $column) => ! $column->isDoneColumn());
            $task->column_id = $fallbackColumn?->id ?? $task->column_id;
        }
    }

    private function syncLabels(KanbanTask $task, array $labels): void
    {
        $labelIds = collect($labels)
            ->map(function (array $label) use ($task) {
                if (! empty($label['id'])) {
                    return $task->board->labels()->find($label['id'])?->id;
                }

                $name = trim((string) ($label['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                return $task->board->labels()->firstOrCreate(
                    ['name' => $name],
                    ['color' => $label['color'] ?? 'slate']
                )->id;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        $task->labels()->sync($labelIds);
    }

    private function syncChecklist(KanbanTask $task, array $items): void
    {
        $task->checklistItems()->delete();

        foreach ($items as $position => $item) {
            $text = trim((string) ($item['text'] ?? ''));

            if ($text === '') {
                continue;
            }

            $task->checklistItems()->create([
                'text' => $text,
                'completed' => (bool) ($item['completed'] ?? false),
                'position' => $position,
            ]);
        }
    }
}
