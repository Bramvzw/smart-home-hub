<?php

namespace Modules\Calendar\Services;

use App\Data\SchedulableGoal;
use Carbon\CarbonImmutable;
use Modules\Calendar\Contracts\PlanComposer;
use Modules\Calendar\Data\BusyTime;
use Modules\Calendar\Data\ComposedPlan;
use Modules\Calendar\Data\PlanItemData;

class WeeklyPlanner
{
    public function __construct(
        private readonly SlotFinder $slotFinder,
        private readonly PlanComposer $composer,
    ) {}

    /**
     * @param  list<BusyTime>  $busy
     * @param  list<SchedulableGoal>  $goals
     */
    public function plan(CarbonImmutable $weekStart, array $busy, array $goals): ComposedPlan
    {
        $placed = [];
        $items = [];

        foreach ($goals as $goal) {
            $slots = $this->slotFinder->slots($goal, $weekStart, array_merge($busy, $placed));
            $min = max(1, $goal->targetMin);
            $max = max($min, $goal->targetMax);
            $placedForGoal = 0;

            // Aim for target_max when slots allow; fall back toward target_min when the week is tight.
            for ($i = 0; $i < $max; $i++) {
                $slot = array_shift($slots);

                if (! $slot) {
                    // Below target_min we couldn't satisfy the goal at all; the rest are extras that
                    // simply didn't fit this week. Both are reported as unplaceable rather than dropped.
                    $reason = $placedForGoal < $min
                        ? 'No suitable free block found'
                        : 'No room for an extra block this week';
                    $items[] = new PlanItemData($goal->id, $goal->title, $goal->category, null, null, 'unplaceable', $reason);

                    continue;
                }

                $items[] = new PlanItemData($goal->id, $goal->title, $goal->category, $slot['start'], $slot['end']);
                $placed[] = new BusyTime($slot['start'], $slot['end']);
                $placedForGoal++;
            }
        }

        $composed = $this->composer->compose($items, $busy);

        if (! $this->valid($composed->items, $busy)) {
            return new ComposedPlan($this->summary($items), $items, true);
        }

        return $composed;
    }

    /**
     * @param  list<PlanItemData>  $items
     * @param  list<BusyTime>  $busy
     */
    public function valid(array $items, array $busy): bool
    {
        $placed = [];

        foreach ($items as $item) {
            if (! $item->start || ! $item->end) {
                continue;
            }

            if ($this->slotFinder->overlapsWork($item->start, $item->end)) {
                return false;
            }

            foreach (array_merge($busy, $placed) as $block) {
                if ($item->start->lt($block->end) && $item->end->gt($block->start)) {
                    return false;
                }
            }

            $placed[] = new BusyTime($item->start, $item->end);
        }

        return true;
    }

    private function summary(array $items): string
    {
        $placed = collect($items)->where('status', 'proposed')->count();
        $unplaced = collect($items)->where('status', 'unplaceable')->count();

        return "Your week plan is ready with {$placed} proposed blocks".($unplaced > 0 ? " and {$unplaced} unscheduled habits." : '.');
    }
}
