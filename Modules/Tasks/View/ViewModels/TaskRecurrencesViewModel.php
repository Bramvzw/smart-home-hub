<?php

namespace Modules\Tasks\View\ViewModels;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\StreakCalculator;

class TaskRecurrencesViewModel
{
    public function __construct(
        private readonly StreakCalculator $streakCalculator,
    ) {
    }

    public function habits(CarbonInterface $date): Collection
    {
        return TaskRecurrence::query()
            ->habits()
            ->active()
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $recurrence): array => $this->habit($recurrence, $date));
    }

    public function habit(TaskRecurrence $recurrence, CarbonInterface $date): array
    {
        return [
            'recurrence' => $recurrence->fresh(),
            'progress' => $this->streakCalculator->progress($recurrence, $date),
            'current_streak' => $this->streakCalculator->currentStreak($recurrence, $date),
            'best_streak' => $this->streakCalculator->bestStreak($recurrence, $date),
            'completed_today' => $this->streakCalculator->isCompleteOn($recurrence, $date),
        ];
    }

    public function maintenance(): Collection
    {
        return TaskRecurrence::query()
            ->maintenance()
            ->with('board')
            ->orderBy('active', 'desc')
            ->orderByRaw('next_due_on is null')
            ->orderBy('next_due_on')
            ->orderBy('title')
            ->get();
    }
}
