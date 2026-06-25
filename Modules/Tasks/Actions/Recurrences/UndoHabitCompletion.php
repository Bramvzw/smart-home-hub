<?php

namespace Modules\Tasks\Actions\Recurrences;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\StreakCalculator;

class UndoHabitCompletion
{
    public function __construct(
        private readonly StreakCalculator $streakCalculator,
    ) {
    }

    public function __invoke(TaskRecurrence $recurrence, ?CarbonInterface $date = null): bool
    {
        if (! $recurrence->isHabit()) {
            throw ValidationException::withMessages([
                'recurrence' => 'Alleen habit-completions kunnen direct worden teruggedraaid.',
            ]);
        }

        $date = $this->date($date);
        $periodKey = $this->streakCalculator->completionPeriodKey($recurrence, $date);

        return $recurrence->completions()->where('period_key', $periodKey)->delete() > 0;
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
