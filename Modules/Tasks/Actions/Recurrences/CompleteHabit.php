<?php

namespace Modules\Tasks\Actions\Recurrences;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Models\TaskRecurrenceCompletion;
use Modules\Tasks\Services\StreakCalculator;

class CompleteHabit
{
    public function __construct(
        private readonly StreakCalculator $streakCalculator,
    ) {
    }

    public function __invoke(TaskRecurrence $recurrence, ?CarbonInterface $date = null): TaskRecurrenceCompletion
    {
        if (! $recurrence->isHabit()) {
            throw ValidationException::withMessages([
                'recurrence' => 'Alleen habits kunnen direct worden afgevinkt.',
            ]);
        }

        $date = $this->date($date);
        $periodKey = $this->streakCalculator->completionPeriodKey($recurrence, $date);

        return $recurrence->completions()->firstOrCreate(
            ['period_key' => $periodKey],
            ['completed_on' => $date->toDateString()]
        );
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
