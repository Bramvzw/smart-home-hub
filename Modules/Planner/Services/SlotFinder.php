<?php

namespace Modules\Planner\Services;

use Carbon\CarbonImmutable;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Models\PlannerIntention;

class SlotFinder
{
    /**
     * @param  list<BusyTime>  $busy
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function slots(PlannerIntention $intention, CarbonImmutable $weekStart, array $busy): array
    {
        $slots = [];

        for ($day = 0; $day < 7; $day++) {
            $date = $weekStart->addDays($day);

            foreach ($this->windows($intention, $date) as [$start, $end]) {
                $cursor = $start;

                while ($cursor->copy()->addMinutes($intention->duration_minutes)->lte($end)) {
                    $slotEnd = $cursor->addMinutes($intention->duration_minutes);

                    if (! $this->overlapsBusy($cursor, $slotEnd, $busy) && ! $this->overlapsWork($cursor, $slotEnd)) {
                        $slots[] = ['start' => $cursor, 'end' => $slotEnd];
                    }

                    $cursor = $cursor->addMinutes(30);
                }
            }
        }

        return $slots;
    }

    private function windows(PlannerIntention $intention, CarbonImmutable $date): array
    {
        $windows = $intention->preferred_windows ?: $this->defaultWindows($intention->category);
        $resolved = [];

        foreach ($windows as $window) {
            if (! $this->dayMatches($window['days'] ?? null, $date)) {
                continue;
            }

            $start = $date->setTimeFromTimeString($window['after'] ?? $window['start'] ?? '10:00');
            $end = $date->setTimeFromTimeString($window['before'] ?? $window['end'] ?? ($date->isWeekend() ? '18:00' : '22:00'));
            $resolved[] = [$start, $end];
        }

        return $resolved;
    }

    private function defaultWindows(string $category): array
    {
        return match ($category) {
            'sport' => [['days' => 'weekday', 'after' => '17:00', 'before' => '22:00'], ['days' => 'weekend', 'after' => '10:00', 'before' => '18:00']],
            'family' => [['days' => 'weekend', 'after' => '10:00', 'before' => '18:00']],
            'date' => [['days' => 'weekend', 'after' => '18:00', 'before' => '23:00']],
            default => [['days' => 'any', 'after' => '10:00', 'before' => '22:00']],
        };
    }

    private function dayMatches(?string $days, CarbonImmutable $date): bool
    {
        return match ($days) {
            'weekday' => $date->isWeekday(),
            'weekend' => $date->isWeekend(),
            'any', null => true,
            default => in_array($date->dayOfWeekIso, array_map('intval', explode(',', $days)), true),
        };
    }

    private function overlapsBusy(CarbonImmutable $start, CarbonImmutable $end, array $busy): bool
    {
        foreach ($busy as $block) {
            if ($start->lt($block->end) && $end->gt($block->start)) {
                return true;
            }
        }

        return false;
    }

    public function overlapsWork(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        $work = config('planner.work_hours');

        if (! in_array($start->dayOfWeekIso, $work['days'], true)) {
            return false;
        }

        $workStart = $start->setTimeFromTimeString($work['start']);
        $workEnd = $start->setTimeFromTimeString($work['end']);

        return $start->lt($workEnd) && $end->gt($workStart);
    }
}
