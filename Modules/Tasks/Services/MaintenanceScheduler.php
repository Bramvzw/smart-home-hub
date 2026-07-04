<?php

namespace Modules\Tasks\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Modules\Tasks\Models\TaskRecurrence;

class MaintenanceScheduler
{
    public function nextDueOn(TaskRecurrence $recurrence, ?CarbonInterface $completedOn = null): CarbonImmutable
    {
        $completedOn = $this->date($completedOn);
        $config = $recurrence->cadence_config ?? [];

        return match ($recurrence->cadence_type) {
            'interval' => $this->intervalNextDueOn($completedOn, $config),
            'weekly' => $this->weeklyNextDueOn($completedOn, $config),
            'monthly' => $this->monthlyNextDueOn($completedOn, $config),
            'annual' => $this->annualNextDueOn($completedOn, $config),
            default => $completedOn->addDay(),
        };
    }

    private function intervalNextDueOn(CarbonImmutable $completedOn, array $config): CarbonImmutable
    {
        $interval = max(1, (int) ($config['interval'] ?? $config['every'] ?? 1));
        $unit = (string) ($config['unit'] ?? 'days');

        return match ($unit) {
            'day', 'days' => $completedOn->addDays($interval),
            'week', 'weeks' => $completedOn->addWeeks($interval),
            'month', 'months' => $completedOn->addMonthsNoOverflow($interval),
            'year', 'years' => $completedOn->addYearsNoOverflow($interval),
            default => $completedOn->addDays($interval),
        };
    }

    private function weeklyNextDueOn(CarbonImmutable $completedOn, array $config): CarbonImmutable
    {
        $weekday = (int) ($config['weekday'] ?? $config['day'] ?? $completedOn->dayOfWeekIso);

        if ($weekday === 0) {
            $weekday = 7;
        }

        $weekday = max(1, min(7, $weekday));
        $cursor = $completedOn->addDay();

        for ($days = 0; $days < 14; $days++) {
            if ($cursor->dayOfWeekIso === $weekday) {
                return $cursor;
            }

            $cursor = $cursor->addDay();
        }

        return $completedOn->addWeek();
    }

    private function monthlyNextDueOn(CarbonImmutable $completedOn, array $config): CarbonImmutable
    {
        $day = max(1, min(31, (int) ($config['day'] ?? $completedOn->day)));

        for ($months = 0; $months < 24; $months++) {
            $month = $completedOn->firstOfMonth()->addMonthsNoOverflow($months);
            $candidate = $month->setDate($month->year, $month->month, min($day, $month->daysInMonth))->startOfDay();

            if ($candidate->greaterThan($completedOn)) {
                return $candidate;
            }
        }

        return $completedOn->addMonthNoOverflow();
    }

    private function annualNextDueOn(CarbonImmutable $completedOn, array $config): CarbonImmutable
    {
        $month = max(1, min(12, (int) ($config['month'] ?? $completedOn->month)));
        $day = max(1, min(31, (int) ($config['day'] ?? $completedOn->day)));
        $candidate = $completedOn
            ->setDate($completedOn->year, $month, min($day, $completedOn->setDate($completedOn->year, $month, 1)->daysInMonth))
            ->startOfDay();

        if ($candidate->lessThanOrEqualTo($completedOn)) {
            $candidate = $completedOn
                ->setDate($completedOn->year + 1, $month, min($day, $completedOn->setDate($completedOn->year + 1, $month, 1)->daysInMonth))
                ->startOfDay();
        }

        return $candidate;
    }

    private function date(?CarbonInterface $date = null): CarbonImmutable
    {
        if ($date) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::now((string) config('app.timezone', 'UTC'))->startOfDay();
    }
}
