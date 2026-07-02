<?php

namespace Modules\Tasks\Services;

use App\Data\HabitCard;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Tasks\Models\TaskRecurrence;

/**
 * Builds the HabitCard shape (management + streak/tracking) for a habit
 * TaskRecurrence. Consolidates what used to live in HabitsPageViewModel so the
 * Calendar tab (via the SchedulableGoals contract) and Tasks share one presenter.
 */
class HabitCardPresenter
{
    private const WEEKDAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    private const DEFAULT_DURATION = 60;

    public function __construct(private readonly StreakCalculator $streakCalculator) {}

    public function present(TaskRecurrence $recurrence, CarbonInterface $date): HabitCard
    {
        $today = CarbonImmutable::instance($date)->startOfDay();
        $config = $recurrence->cadence_config ?? [];

        $progress = $this->streakCalculator->progress($recurrence, $today);
        $completedToday = $this->streakCalculator->isCompleteOn($recurrence, $today);
        $isCount = in_array($recurrence->cadence_type, ['times_per_week', 'weekly', 'monthly'], true);

        $week = $isCount ? [] : $this->weekStrip($recurrence, $today);
        $restToday = ! $isCount && $this->todayStatus($week) === 'off';

        [$min, $max] = $this->targetRange($recurrence, $config);

        return new HabitCard(
            id: $recurrence->id,
            title: $recurrence->title,
            category: (string) ($config['category'] ?? 'custom'),
            frequencyType: $recurrence->cadence_type === 'weekly' ? 'weekly' : 'times_per_week',
            durationMinutes: $recurrence->duration_minutes ?? self::DEFAULT_DURATION,
            targetMin: $min,
            targetMax: $max,
            preferredWindows: is_array($config['preferred_windows'] ?? null) ? $config['preferred_windows'] : [],
            plannable: (bool) $recurrence->plannable,
            active: (bool) $recurrence->active,
            icon: $this->habitIcon($recurrence->title),
            cadenceLabel: $this->cadenceLabel($recurrence),
            type: $isCount ? 'count' : 'week',
            target: $progress->target,
            done: $progress->completed,
            reached: $progress->isComplete(),
            week: $week,
            weekDone: count(array_filter($week, static fn (array $d): bool => $d['status'] === 'done')),
            weekTotal: count(array_filter($week, static fn (array $d): bool => $d['status'] !== 'off')),
            streak: $this->streakCalculator->currentStreak($recurrence, $today),
            best: $this->streakCalculator->bestStreak($recurrence, $today),
            completedToday: $completedToday,
            restToday: $restToday,
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function targetRange(TaskRecurrence $recurrence, array $config): array
    {
        $fromCadence = match ($recurrence->cadence_type) {
            'times_per_week' => max(1, (int) ($config['times'] ?? $config['target'] ?? $config['count'] ?? 1)),
            'weekdays' => max(1, count($this->scheduledWeekdays($recurrence))),
            default => 1,
        };

        $min = max(1, (int) ($config['target_min'] ?? $fromCadence));
        $max = max($min, (int) ($config['target_max'] ?? $fromCadence));

        return [$min, $max];
    }

    private function weekStrip(TaskRecurrence $recurrence, CarbonImmutable $today): array
    {
        $start = $today->startOfWeek(CarbonInterface::MONDAY);
        $end = $start->addDays(6);
        $scheduled = $this->scheduledWeekdays($recurrence);

        $completed = array_flip($recurrence->completions()
            ->whereBetween('completed_on', [$start->toDateString(), $end->toDateString()])
            ->pluck('period_key')
            ->all());

        $strip = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $start->addDays($i);
            $isScheduled = in_array($day->dayOfWeekIso, $scheduled, true);

            if (! $isScheduled) {
                $status = 'off';
            } elseif (isset($completed[$day->toDateString()])) {
                $status = 'done';
            } elseif ($day->lessThan($today)) {
                $status = 'miss';
            } else {
                $status = 'sched';
            }

            $strip[] = ['label' => self::WEEKDAY_LABELS[$i], 'status' => $status, 'today' => $day->isSameDay($today)];
        }

        return $strip;
    }

    private function todayStatus(array $week): string
    {
        foreach ($week as $day) {
            if ($day['today']) {
                return $day['status'];
            }
        }

        return 'off';
    }

    private function cadenceLabel(TaskRecurrence $recurrence): string
    {
        $config = $recurrence->cadence_config ?? [];

        return match ($recurrence->cadence_type) {
            'times_per_week' => max(1, (int) ($config['times'] ?? $config['target'] ?? $config['count'] ?? 1)).'× per week',
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'weekdays' => $this->weekdaysLabel($recurrence),
            default => 'daily',
        };
    }

    private function weekdaysLabel(TaskRecurrence $recurrence): string
    {
        $days = $this->scheduledWeekdays($recurrence);

        if (count($days) === 7) {
            return 'daily';
        }

        return collect($days)->map(static fn (int $iso): string => self::WEEKDAY_LABELS[$iso - 1])->implode(' / ');
    }

    /**
     * @return list<int>
     */
    private function scheduledWeekdays(TaskRecurrence $recurrence): array
    {
        $weekdays = $recurrence->cadence_config['weekdays'] ?? $recurrence->cadence_config['days'] ?? [];

        if (! is_array($weekdays) || $weekdays === []) {
            return [1, 2, 3, 4, 5, 6, 7];
        }

        $mapped = collect($weekdays)
            ->map(fn (mixed $day): ?int => $this->mapWeekday($day))
            ->filter()->unique()->sort()->values()->all();

        return $mapped === [] ? [1, 2, 3, 4, 5, 6, 7] : $mapped;
    }

    private function mapWeekday(mixed $day): ?int
    {
        if (is_numeric($day)) {
            $number = (int) $day;

            return $number === 0 ? 7 : ($number >= 1 && $number <= 7 ? $number : null);
        }

        return [
            'mon' => 1, 'monday' => 1, 'ma' => 1, 'maandag' => 1,
            'tue' => 2, 'tuesday' => 2, 'di' => 2, 'dinsdag' => 2,
            'wed' => 3, 'wednesday' => 3, 'wo' => 3, 'woensdag' => 3,
            'thu' => 4, 'thursday' => 4, 'do' => 4, 'donderdag' => 4,
            'fri' => 5, 'friday' => 5, 'vr' => 5, 'vrijdag' => 5,
            'sat' => 6, 'saturday' => 6, 'za' => 6, 'zaterdag' => 6,
            'sun' => 7, 'sunday' => 7, 'zo' => 7, 'zondag' => 7,
        ][mb_strtolower(trim((string) $day))] ?? null;
    }

    private function habitIcon(string $title): string
    {
        $t = mb_strtolower($title);

        return match (true) {
            str_contains($t, 'sport') || str_contains($t, 'hardlop') || str_contains($t, 'gym') || str_contains($t, 'fitness') || str_contains($t, 'kracht') || str_contains($t, 'wandel')
                || str_contains($t, 'exercise') || str_contains($t, 'run') || str_contains($t, 'workout') || str_contains($t, 'walk') || str_contains($t, 'strength') => 'Activity',
            str_contains($t, 'lees') || str_contains($t, 'lezen') || str_contains($t, 'boek') || str_contains($t, 'read') || str_contains($t, 'book') => 'Book',
            str_contains($t, 'medit') || str_contains($t, 'mindful') || str_contains($t, 'ademhal') || str_contains($t, 'breath') => 'Spark',
            str_contains($t, 'spaans') || str_contains($t, 'taal') || str_contains($t, 'leren') || str_contains($t, 'engels') || str_contains($t, 'frans') || str_contains($t, 'duits')
                || str_contains($t, 'language') || str_contains($t, 'learn') || str_contains($t, 'spanish') || str_contains($t, 'french') || str_contains($t, 'study') => 'Target',
            str_contains($t, 'water') || str_contains($t, 'drink') || str_contains($t, 'hydrat') => 'Drop',
            default => 'Flame',
        };
    }
}
