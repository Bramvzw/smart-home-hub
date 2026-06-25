<?php

namespace Modules\Tasks\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Tasks\Data\HabitProgress;
use Modules\Tasks\Models\TaskRecurrence;

class StreakCalculator
{
    public function progress(TaskRecurrence $recurrence, ?CarbonInterface $date = null): HabitProgress
    {
        $date = $this->date($date);

        return match ($recurrence->cadence_type) {
            'times_per_week' => $this->weeklyProgress($recurrence, $date),
            'weekdays' => $this->weekdaysProgress($recurrence, $date),
            'weekly' => new HabitProgress(
                periodKey: $this->weekKey($date),
                completed: $this->hasCompletion($recurrence, $this->weekKey($date)) ? 1 : 0,
                target: 1,
            ),
            'monthly' => new HabitProgress(
                periodKey: $this->monthKey($date),
                completed: $this->hasCompletion($recurrence, $this->monthKey($date)) ? 1 : 0,
                target: 1,
            ),
            default => new HabitProgress(
                periodKey: $this->completionPeriodKey($recurrence, $date),
                completed: $this->isCompleteOn($recurrence, $date) ? 1 : 0,
                target: 1,
            ),
        };
    }

    public function currentStreak(TaskRecurrence $recurrence, ?CarbonInterface $date = null): int
    {
        $date = $this->date($date);

        return match ($recurrence->cadence_type) {
            'times_per_week', 'weekly' => $this->currentPeriodStreak($recurrence, $date, 'week'),
            'monthly' => $this->currentPeriodStreak($recurrence, $date, 'month'),
            'weekdays' => $this->currentScheduledDateStreak($recurrence, $date),
            default => $this->currentDailyDateStreak($recurrence, $date),
        };
    }

    public function bestStreak(TaskRecurrence $recurrence, ?CarbonInterface $date = null): int
    {
        $date = $this->date($date);

        return match ($recurrence->cadence_type) {
            'times_per_week', 'weekly' => $this->bestPeriodStreak($recurrence, $date, 'week'),
            'monthly' => $this->bestPeriodStreak($recurrence, $date, 'month'),
            'weekdays' => $this->bestScheduledDateStreak($recurrence, $date),
            default => $this->bestDailyDateStreak($recurrence, $date),
        };
    }

    public function completionPeriodKey(TaskRecurrence $recurrence, CarbonInterface $date): string
    {
        $date = $this->date($date);

        return match ($recurrence->cadence_type) {
            'weekly' => $this->weekKey($date),
            'monthly' => $this->monthKey($date),
            default => $date->toDateString(),
        };
    }

    public function isCompleteOn(TaskRecurrence $recurrence, CarbonInterface $date): bool
    {
        return $this->hasCompletion($recurrence, $this->completionPeriodKey($recurrence, $date));
    }

    private function weeklyProgress(TaskRecurrence $recurrence, CarbonImmutable $date): HabitProgress
    {
        [$start, $end] = $this->weekBounds($date);

        return new HabitProgress(
            periodKey: $this->weekKey($date),
            completed: $this->completionsBetween($recurrence, $start, $end),
            target: $this->weeklyTarget($recurrence),
        );
    }

    private function weekdaysProgress(TaskRecurrence $recurrence, CarbonImmutable $date): HabitProgress
    {
        [$start, $end] = $this->weekBounds($date);
        $periodKeys = collect($this->scheduledDatesBetween($recurrence, $start, $end))
            ->map(fn (CarbonImmutable $scheduled): string => $scheduled->toDateString())
            ->all();

        return new HabitProgress(
            periodKey: $this->weekKey($date),
            completed: empty($periodKeys)
                ? 0
                : $recurrence->completions()->whereIn('period_key', $periodKeys)->count(),
            target: count($periodKeys),
        );
    }

    private function currentPeriodStreak(TaskRecurrence $recurrence, CarbonImmutable $date, string $unit): int
    {
        $cursor = $this->periodStart($date, $unit);

        if (! $this->periodSucceeded($recurrence, $cursor, $unit)) {
            $cursor = $this->subtractPeriod($cursor, $unit);
        }

        $streak = 0;

        while ($this->periodSucceeded($recurrence, $cursor, $unit)) {
            $streak++;
            $cursor = $this->subtractPeriod($cursor, $unit);
        }

        return $streak;
    }

    private function bestPeriodStreak(TaskRecurrence $recurrence, CarbonImmutable $date, string $unit): int
    {
        $firstCompletion = $recurrence->completions()->min('completed_on');

        if (! $firstCompletion) {
            return 0;
        }

        $cursor = $this->periodStart(CarbonImmutable::parse($firstCompletion), $unit);
        $last = $this->periodStart($date, $unit);
        $current = 0;
        $best = 0;

        while ($cursor->lessThanOrEqualTo($last)) {
            if ($this->periodSucceeded($recurrence, $cursor, $unit)) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 0;
            }

            $cursor = $this->addPeriod($cursor, $unit);
        }

        return $best;
    }

    private function currentScheduledDateStreak(TaskRecurrence $recurrence, CarbonImmutable $date): int
    {
        $cursor = $this->latestScheduledDateOnOrBefore($recurrence, $date);

        if (! $cursor) {
            return 0;
        }

        if ($cursor->isSameDay($date) && ! $this->isCompleteOn($recurrence, $cursor)) {
            $cursor = $this->previousScheduledDate($recurrence, $cursor->subDay());
        }

        $streak = 0;

        while ($cursor && $this->isCompleteOn($recurrence, $cursor)) {
            $streak++;
            $cursor = $this->previousScheduledDate($recurrence, $cursor->subDay());
        }

        return $streak;
    }

    private function bestScheduledDateStreak(TaskRecurrence $recurrence, CarbonImmutable $date): int
    {
        $firstCompletion = $recurrence->completions()->min('completed_on');

        if (! $firstCompletion) {
            return 0;
        }

        $cursor = CarbonImmutable::parse($firstCompletion)->startOfDay();
        $current = 0;
        $best = 0;

        while ($cursor->lessThanOrEqualTo($date)) {
            if (! $this->isScheduledOn($recurrence, $cursor)) {
                $cursor = $cursor->addDay();

                continue;
            }

            if ($this->isCompleteOn($recurrence, $cursor)) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 0;
            }

            $cursor = $cursor->addDay();
        }

        return $best;
    }

    private function currentDailyDateStreak(TaskRecurrence $recurrence, CarbonImmutable $date): int
    {
        $cursor = $date;

        if (! $this->isCompleteOn($recurrence, $cursor)) {
            $cursor = $cursor->subDay();
        }

        $streak = 0;

        while ($this->isCompleteOn($recurrence, $cursor)) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    private function bestDailyDateStreak(TaskRecurrence $recurrence, CarbonImmutable $date): int
    {
        $firstCompletion = $recurrence->completions()->min('completed_on');

        if (! $firstCompletion) {
            return 0;
        }

        $cursor = CarbonImmutable::parse($firstCompletion)->startOfDay();
        $current = 0;
        $best = 0;

        while ($cursor->lessThanOrEqualTo($date)) {
            if ($this->isCompleteOn($recurrence, $cursor)) {
                $current++;
                $best = max($best, $current);
            } else {
                $current = 0;
            }

            $cursor = $cursor->addDay();
        }

        return $best;
    }

    private function periodSucceeded(TaskRecurrence $recurrence, CarbonImmutable $periodStart, string $unit): bool
    {
        if ($recurrence->cadence_type === 'times_per_week') {
            $end = $unit === 'month' ? $periodStart->endOfMonth() : $periodStart->addDays(6);

            return $this->completionsBetween($recurrence, $periodStart, $end) >= $this->weeklyTarget($recurrence);
        }

        $key = $unit === 'month' ? $this->monthKey($periodStart) : $this->weekKey($periodStart);

        return $this->hasCompletion($recurrence, $key);
    }

    private function completionsBetween(TaskRecurrence $recurrence, CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $recurrence->completions()
            ->whereBetween('completed_on', [$start->toDateString(), $end->toDateString()])
            ->count();
    }

    private function hasCompletion(TaskRecurrence $recurrence, string $periodKey): bool
    {
        return $recurrence->completions()->where('period_key', $periodKey)->exists();
    }

    private function weekBounds(CarbonImmutable $date): array
    {
        $start = $date->startOfWeek(CarbonInterface::MONDAY)->startOfDay();

        return [$start, $start->addDays(6)->endOfDay()];
    }

    private function weekKey(CarbonImmutable $date): string
    {
        return $date->format('o-\WW');
    }

    private function monthKey(CarbonImmutable $date): string
    {
        return $date->format('Y-m');
    }

    private function periodStart(CarbonImmutable $date, string $unit): CarbonImmutable
    {
        if ($unit === 'month') {
            return $date->firstOfMonth()->startOfDay();
        }

        return $date->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
    }

    private function addPeriod(CarbonImmutable $date, string $unit): CarbonImmutable
    {
        return $unit === 'month' ? $date->addMonthNoOverflow() : $date->addWeek();
    }

    private function subtractPeriod(CarbonImmutable $date, string $unit): CarbonImmutable
    {
        return $unit === 'month' ? $date->subMonthNoOverflow() : $date->subWeek();
    }

    private function weeklyTarget(TaskRecurrence $recurrence): int
    {
        $config = $recurrence->cadence_config ?? [];

        return max(1, (int) ($config['times'] ?? $config['target'] ?? $config['count'] ?? 1));
    }

    private function scheduledDatesBetween(TaskRecurrence $recurrence, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];
        $cursor = $start->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($this->isScheduledOn($recurrence, $cursor)) {
                $dates[] = $cursor;
            }

            $cursor = $cursor->addDay();
        }

        return $dates;
    }

    private function latestScheduledDateOnOrBefore(TaskRecurrence $recurrence, CarbonImmutable $date): ?CarbonImmutable
    {
        $cursor = $date->startOfDay();

        for ($days = 0; $days < 3700; $days++) {
            if ($this->isScheduledOn($recurrence, $cursor)) {
                return $cursor;
            }

            $cursor = $cursor->subDay();
        }

        return null;
    }

    private function previousScheduledDate(TaskRecurrence $recurrence, CarbonImmutable $date): ?CarbonImmutable
    {
        return $this->latestScheduledDateOnOrBefore($recurrence, $date);
    }

    private function isScheduledOn(TaskRecurrence $recurrence, CarbonImmutable $date): bool
    {
        return in_array($date->dayOfWeekIso, $this->scheduledWeekdays($recurrence), true);
    }

    private function scheduledWeekdays(TaskRecurrence $recurrence): array
    {
        $weekdays = $recurrence->cadence_config['weekdays'] ?? $recurrence->cadence_config['days'] ?? [];

        if (! is_array($weekdays) || $weekdays === []) {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        $mapped = collect($weekdays)
            ->map(fn (mixed $day): ?int => $this->mapWeekday($day))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $mapped === [] ? [1, 2, 3, 4, 5, 6, 7] : $mapped;
    }

    private function mapWeekday(mixed $day): ?int
    {
        if (is_numeric($day)) {
            $number = (int) $day;

            return $number === 0 ? 7 : ($number >= 1 && $number <= 7 ? $number : null);
        }

        return [
            'mon' => 1,
            'monday' => 1,
            'ma' => 1,
            'maandag' => 1,
            'tue' => 2,
            'tuesday' => 2,
            'di' => 2,
            'dinsdag' => 2,
            'wed' => 3,
            'wednesday' => 3,
            'wo' => 3,
            'woensdag' => 3,
            'thu' => 4,
            'thursday' => 4,
            'do' => 4,
            'donderdag' => 4,
            'fri' => 5,
            'friday' => 5,
            'vr' => 5,
            'vrijdag' => 5,
            'sat' => 6,
            'saturday' => 6,
            'za' => 6,
            'zaterdag' => 6,
            'sun' => 7,
            'sunday' => 7,
            'zo' => 7,
            'zondag' => 7,
        ][mb_strtolower(trim((string) $day))] ?? null;
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
