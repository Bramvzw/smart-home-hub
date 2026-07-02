<?php

namespace Modules\Tasks\View\ViewModels;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Tasks\Models\TaskRecurrence;

/**
 * Read-side presentation model for the Maintenance page: recurring maintenance
 * tasks with due labels and board status. Habit tracking now lives on the
 * Calendar Habits tab (via the SchedulableGoals contract + HabitCardPresenter).
 */
class MaintenancePageViewModel
{
    public function pageData(CarbonInterface $date): array
    {
        $today = CarbonImmutable::instance($date)->startOfDay();

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

        return [
            'date' => $today->toDateString(),
            'today_label' => $today->locale('en')->isoFormat('dddd D MMMM'),
            'maintenance' => $maintenance,
            'maintenance_count' => count($maintenance),
            'overdue_count' => count(array_filter($maintenance, static fn (array $m): bool => $m['status'] === 'overdue')),
            'soon_count' => count(array_filter($maintenance, static fn (array $m): bool => $m['status'] === 'soon')),
        ];
    }

    private function presentMaintenance(TaskRecurrence $recurrence, CarbonImmutable $today): array
    {
        $due = $recurrence->next_due_on
            ? CarbonImmutable::instance($recurrence->next_due_on)->startOfDay()
            : null;

        $lastCompletion = $recurrence->completions()->max('completed_on');
        $last = $lastCompletion
            ? CarbonImmutable::parse($lastCompletion)
            : ($recurrence->last_materialized_on ? CarbonImmutable::instance($recurrence->last_materialized_on) : null);

        return [
            'id' => $recurrence->id,
            'title' => $recurrence->title,
            'icon' => $this->maintenanceIcon($recurrence->title),
            'cadence_label' => $this->maintenanceCadenceLabel($recurrence),
            'status' => $this->maintenanceStatus($due, $today),
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

        return $due->lessThanOrEqualTo($today->addDays(7)) ? 'soon' : 'ok';
    }

    private function dueRelative(?CarbonImmutable $due, CarbonImmutable $today): string
    {
        if (! $due) {
            return 'no date';
        }

        $days = (int) $today->diffInDays($due, false);

        if ($days < 0) {
            $n = abs($days);

            return $n.' '.($n === 1 ? 'day' : 'days').' overdue';
        }

        if ($days === 0) {
            return 'today';
        }

        if ($days > 45) {
            $months = (int) round($days / 30);

            return 'in ~'.$months.' '.($months === 1 ? 'month' : 'months');
        }

        return 'in '.$days.' '.($days === 1 ? 'day' : 'days');
    }

    private function formatDate(CarbonImmutable $date, CarbonImmutable $today): string
    {
        return $date->locale('en')->isoFormat($date->year === $today->year ? 'D MMM' : 'MMM YYYY');
    }

    private function maintenanceCadenceLabel(TaskRecurrence $recurrence): string
    {
        $config = $recurrence->cadence_config ?? [];

        return match ($recurrence->cadence_type) {
            'interval' => $this->intervalLabel(
                max(1, (int) ($config['interval'] ?? $config['every'] ?? 1)),
                (string) ($config['unit'] ?? 'days'),
            ),
            'weekly' => 'weekly',
            'monthly' => 'monthly',
            'annual' => 'annually',
            default => 'recurring',
        };
    }

    private function intervalLabel(int $interval, string $unit): string
    {
        $labels = [
            'day' => ['day', 'days'], 'days' => ['day', 'days'],
            'week' => ['week', 'weeks'], 'weeks' => ['week', 'weeks'],
            'month' => ['month', 'months'], 'months' => ['month', 'months'],
            'year' => ['year', 'years'], 'years' => ['year', 'years'],
        ];
        [$singular, $plural] = $labels[$unit] ?? ['period', 'periods'];

        return $interval === 1 ? 'every '.$singular : 'every '.$interval.' '.$plural;
    }

    private function maintenanceIcon(string $title): string
    {
        $t = mb_strtolower($title);

        return match (true) {
            str_contains($t, 'rook') || str_contains($t, 'melder') || str_contains($t, 'alarm') || str_contains($t, 'batterij')
                || str_contains($t, 'smoke') || str_contains($t, 'detector') || str_contains($t, 'battery') => 'Bell',
            str_contains($t, 'moestuin') || str_contains($t, 'tuin') || str_contains($t, 'zaai') || str_contains($t, 'plant') || str_contains($t, 'snoei')
                || str_contains($t, 'garden') || str_contains($t, 'sow') || str_contains($t, 'prune') => 'Leaf',
            str_contains($t, 'filter') || str_contains($t, 'cv') || str_contains($t, 'dakgoot') || str_contains($t, 'lek') || str_contains($t, 'water')
                || str_contains($t, 'gutter') || str_contains($t, 'leak') || str_contains($t, 'boiler') => 'Drop',
            default => 'Wrench',
        };
    }
}
