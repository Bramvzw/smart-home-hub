<?php

namespace Modules\Tasks\View\ViewModels;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Tasks\Models\TaskRecurrence;
use Modules\Tasks\Services\StreakCalculator;

/**
 * Read-side presentation model for the Gewoontes & Onderhoud HTML page. Turns
 * the raw recurrence + completion data into the shapes the redesign needs:
 * weekly progress strips, segment bars, streaks and maintenance due labels.
 */
class HabitsPageViewModel
{
    private const WEEKDAY_LABELS = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'];

    public function __construct(
        private readonly StreakCalculator $streakCalculator,
    ) {
    }

    public function pageData(CarbonInterface $date): array
    {
        $today = CarbonImmutable::instance($date)->startOfDay();

        $habits = TaskRecurrence::query()
            ->habits()
            ->active()
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $recurrence): array => $this->presentHabit($recurrence, $today))
            ->values()
            ->all();

        $maintenance = TaskRecurrence::query()
            ->maintenance()
            ->orderBy('active', 'desc')
            ->orderByRaw('next_due_on is null')
            ->orderBy('next_due_on')
            ->orderBy('title')
            ->get()
            ->map(fn (TaskRecurrence $recurrence): array => $this->presentMaintenance($recurrence, $today))
            ->values()
            ->all();

        $doneToday = count(array_filter($habits, static fn (array $h): bool => $h['completed_today']));
        $actionableToday = count(array_filter($habits, static fn (array $h): bool => ! $h['rest_today']));
        $overdueCount = count(array_filter($maintenance, static fn (array $m): bool => $m['status'] === 'overdue'));
        $soonCount = count(array_filter($maintenance, static fn (array $m): bool => $m['status'] === 'soon'));

        return [
            'date' => $today->toDateString(),
            'today_label' => $today->locale('nl')->isoFormat('dddd D MMMM'),
            'habits' => $habits,
            'maintenance' => $maintenance,
            'habit_count' => count($habits),
            'maintenance_count' => count($maintenance),
            'done_today' => $doneToday,
            'actionable_today' => $actionableToday,
            'overdue_count' => $overdueCount,
            'soon_count' => $soonCount,
        ];
    }

    private function presentHabit(TaskRecurrence $recurrence, CarbonImmutable $today): array
    {
        $progress = $this->streakCalculator->progress($recurrence, $today);
        $completedToday = $this->streakCalculator->isCompleteOn($recurrence, $today);
        $isCount = $recurrence->cadence_type === 'times_per_week'
            || $recurrence->cadence_type === 'weekly'
            || $recurrence->cadence_type === 'monthly';

        $week = $isCount ? [] : $this->weekStrip($recurrence, $today);
        $restToday = ! $isCount && $this->todayStatus($week) === 'off';

        return [
            'id' => $recurrence->id,
            'title' => $recurrence->title,
            'icon' => $this->habitIcon($recurrence->title),
            'cadence_label' => $this->habitCadenceLabel($recurrence),
            'type' => $isCount ? 'count' : 'week',
            'target' => $progress->target,
            'done' => $progress->completed,
            'reached' => $progress->isComplete(),
            'week' => $week,
            'week_done' => count(array_filter($week, static fn (array $d): bool => $d['status'] === 'done')),
            'week_total' => count(array_filter($week, static fn (array $d): bool => $d['status'] !== 'off')),
            'streak' => $this->streakCalculator->currentStreak($recurrence, $today),
            'best' => $this->streakCalculator->bestStreak($recurrence, $today),
            'completed_today' => $completedToday,
            'rest_today' => $restToday,
            'complete_url' => route('tasks.habits.complete', $recurrence->id),
        ];
    }

    private function weekStrip(TaskRecurrence $recurrence, CarbonImmutable $today): array
    {
        $start = $today->startOfWeek(CarbonInterface::MONDAY);
        $end = $start->addDays(6);
        $scheduled = $this->scheduledWeekdays($recurrence);

        $completed = $recurrence->completions()
            ->whereBetween('completed_on', [$start->toDateString(), $end->toDateString()])
            ->pluck('period_key')
            ->all();
        $completed = array_flip($completed);

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

            $strip[] = [
                'label' => self::WEEKDAY_LABELS[$i],
                'status' => $status,
                'today' => $day->isSameDay($today),
            ];
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

    private function presentMaintenance(TaskRecurrence $recurrence, CarbonImmutable $today): array
    {
        $due = $recurrence->next_due_on
            ? CarbonImmutable::instance($recurrence->next_due_on)->startOfDay()
            : null;

        $status = $this->maintenanceStatus($due, $today);
        $lastCompletion = $recurrence->completions()->max('completed_on');
        $last = $lastCompletion
            ? CarbonImmutable::parse($lastCompletion)
            : ($recurrence->last_materialized_on ? CarbonImmutable::instance($recurrence->last_materialized_on) : null);

        return [
            'id' => $recurrence->id,
            'title' => $recurrence->title,
            'icon' => $this->maintenanceIcon($recurrence->title),
            'cadence_label' => $this->maintenanceCadenceLabel($recurrence),
            'status' => $status,
            'due_rel' => $this->dueRelative($due, $today),
            'due_abs' => $due ? $this->formatDate($due, $today) : '—',
            'last_label' => $last ? $this->formatDate($last, $today) : null,
            'on_board' => $recurrence->tasks()->whereNull('archived_at')->where('completed', false)->exists(),
            'active' => (bool) $recurrence->active,
            'complete_url' => route('tasks.maintenance.complete', $recurrence->id),
        ];
    }

    private function maintenanceStatus(?CarbonImmutable $due, CarbonImmutable $today): string
    {
        if (! $due) {
            return 'ok';
        }

        if ($due->lessThan($today)) {
            return 'overdue';
        }

        if ($due->lessThanOrEqualTo($today->addDays(7))) {
            return 'soon';
        }

        return 'ok';
    }

    private function dueRelative(?CarbonImmutable $due, CarbonImmutable $today): string
    {
        if (! $due) {
            return 'geen datum';
        }

        $days = (int) $today->diffInDays($due, false);

        if ($days < 0) {
            $n = abs($days);

            return $n.' '.($n === 1 ? 'dag' : 'dagen').' te laat';
        }

        if ($days === 0) {
            return 'vandaag';
        }

        if ($days > 45) {
            $months = (int) round($days / 30);

            return 'over ~'.$months.' '.($months === 1 ? 'maand' : 'maanden');
        }

        return 'over '.$days.' '.($days === 1 ? 'dag' : 'dagen');
    }

    private function formatDate(CarbonImmutable $date, CarbonImmutable $today): string
    {
        $format = $date->year === $today->year ? 'D MMM' : 'MMM YYYY';

        return $date->locale('nl')->isoFormat($format);
    }

    private function habitCadenceLabel(TaskRecurrence $recurrence): string
    {
        $config = $recurrence->cadence_config ?? [];

        return match ($recurrence->cadence_type) {
            'times_per_week' => max(1, (int) ($config['times'] ?? $config['target'] ?? $config['count'] ?? 1)).'× per week',
            'weekly' => 'wekelijks',
            'monthly' => 'maandelijks',
            'weekdays' => $this->weekdaysLabel($recurrence),
            default => 'dagelijks',
        };
    }

    private function weekdaysLabel(TaskRecurrence $recurrence): string
    {
        $days = $this->scheduledWeekdays($recurrence);

        if (count($days) === 7) {
            return 'dagelijks';
        }

        return collect($days)
            ->map(static fn (int $iso): string => self::WEEKDAY_LABELS[$iso - 1])
            ->implode(' / ');
    }

    private function maintenanceCadenceLabel(TaskRecurrence $recurrence): string
    {
        $config = $recurrence->cadence_config ?? [];

        return match ($recurrence->cadence_type) {
            'interval' => $this->intervalLabel(
                max(1, (int) ($config['interval'] ?? $config['every'] ?? 1)),
                (string) ($config['unit'] ?? 'days'),
            ),
            'weekly' => 'wekelijks',
            'monthly' => 'maandelijks',
            'annual' => 'jaarlijks',
            default => 'terugkerend',
        };
    }

    private function intervalLabel(int $interval, string $unit): string
    {
        $labels = [
            'day' => ['dag', 'dagen'],
            'days' => ['dag', 'dagen'],
            'week' => ['week', 'weken'],
            'weeks' => ['week', 'weken'],
            'month' => ['maand', 'maanden'],
            'months' => ['maand', 'maanden'],
            'year' => ['jaar', 'jaar'],
            'years' => ['jaar', 'jaar'],
        ];
        [$singular, $plural] = $labels[$unit] ?? ['periode', 'periodes'];

        return $interval === 1
            ? 'elke '.$singular
            : 'elke '.$interval.' '.$plural;
    }

    /**
     * @return list<int> ISO weekday numbers (1=Mon … 7=Sun)
     */
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
            str_contains($t, 'sport') || str_contains($t, 'hardlop') || str_contains($t, 'gym') || str_contains($t, 'fitness') || str_contains($t, 'kracht') || str_contains($t, 'wandel') => 'Activity',
            str_contains($t, 'lees') || str_contains($t, 'lezen') || str_contains($t, 'boek') => 'Book',
            str_contains($t, 'medit') || str_contains($t, 'mindful') || str_contains($t, 'ademhal') => 'Spark',
            str_contains($t, 'spaans') || str_contains($t, 'taal') || str_contains($t, 'leren') || str_contains($t, 'engels') || str_contains($t, 'frans') || str_contains($t, 'duits') => 'Target',
            str_contains($t, 'water') || str_contains($t, 'drink') || str_contains($t, 'hydrat') => 'Drop',
            default => 'Flame',
        };
    }

    private function maintenanceIcon(string $title): string
    {
        $t = mb_strtolower($title);

        return match (true) {
            str_contains($t, 'rook') || str_contains($t, 'melder') || str_contains($t, 'alarm') || str_contains($t, 'batterij') => 'Bell',
            str_contains($t, 'moestuin') || str_contains($t, 'tuin') || str_contains($t, 'zaai') || str_contains($t, 'plant') || str_contains($t, 'snoei') => 'Leaf',
            str_contains($t, 'filter') || str_contains($t, 'cv') || str_contains($t, 'dakgoot') || str_contains($t, 'lek') || str_contains($t, 'water') => 'Drop',
            default => 'Wrench',
        };
    }
}
