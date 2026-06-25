<?php

namespace Modules\Tasks\Briefing;

use App\Contracts\BriefingSource;
use App\Support\Briefing\BriefingSection;
use Carbon\CarbonImmutable;
use Modules\Tasks\Models\KanbanTask;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\StreakCalculator;

class TasksBriefingSource implements BriefingSource
{
    public function __construct(
        private readonly StreakCalculator $streakCalculator,
    ) {
    }

    public function key(): string
    {
        return 'tasks';
    }

    public function label(): string
    {
        return 'Taken';
    }

    public function priority(): int
    {
        return 30;
    }

    public function contribute(CarbonImmutable $date): ?BriefingSection
    {
        $limit = max(1, (int) config('briefing.tasks_limit', 3));
        $tasks = KanbanTask::query()
            ->with(['board', 'column'])
            ->active()
            ->where('completed', false)
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('position')
            ->limit($limit)
            ->get();
        $habits = TaskRecurrence::query()
            ->habits()
            ->active()
            ->orderBy('title')
            ->get();

        if ($tasks->isEmpty() && $habits->isEmpty()) {
            return null;
        }

        $titles = $tasks->pluck('title')->all();
        $habitData = $habits->map(function (TaskRecurrence $habit) use ($date): array {
            $progress = $this->streakCalculator->progress($habit, $date);

            return [
                'title' => $habit->title,
                'cadence_type' => $habit->cadence_type,
                'progress' => $progress->toArray(),
                'current_streak' => $this->streakCalculator->currentStreak($habit, $date),
                'best_streak' => $this->streakCalculator->bestStreak($habit, $date),
                'completed_today' => $this->streakCalculator->isCompleteOn($habit, $date),
            ];
        })->values();
        $summaries = [];

        if ($titles !== []) {
            $summaries[] = 'Top '.count($titles).' open taak'.(count($titles) === 1 ? '' : 'en').': '.implode(', ', $titles);
        }

        if ($habitData->isNotEmpty()) {
            $summaries[] = 'Habits vandaag: '.$habitData
                ->map(fn (array $habit): string => $habit['title'].' '.$habit['progress']['completed'].'/'.$habit['progress']['target'])
                ->implode(', ');
        }

        return new BriefingSection(
            key: $this->key(),
            label: $this->label(),
            priority: $this->priority(),
            summary: implode(' | ', $summaries),
            data: [
                'tasks' => $tasks->map(fn (KanbanTask $task): array => [
                    'title' => $task->title,
                    'description' => $task->description,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->toDateString(),
                    'board' => $task->board?->name,
                    'column' => $task->column?->name,
                ])->values()->all(),
                'habits' => $habitData->all(),
                'date' => $date->toDateString(),
            ],
        );
    }
}
