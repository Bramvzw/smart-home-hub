<?php

namespace Modules\Planner\Services;

use Carbon\CarbonImmutable;
use Modules\Planner\Contracts\PlanComposer;
use Modules\Planner\Data\BusyTime;
use Modules\Planner\Data\ComposedPlan;
use Modules\Planner\Data\PlanItemData;
use Modules\Planner\Models\PlannerIntention;

class WeeklyPlanner
{
    public function __construct(
        private readonly SlotFinder $slotFinder,
        private readonly PlanComposer $composer,
    ) {}

    /**
     * @param  list<BusyTime>  $busy
     */
    public function plan(CarbonImmutable $weekStart, array $busy): ComposedPlan
    {
        $intentions = PlannerIntention::query()->where('active', true)->orderBy('category')->get();
        $placed = [];
        $items = [];

        foreach ($intentions as $intention) {
            $slots = $this->slotFinder->slots($intention, $weekStart, array_merge($busy, $placed));
            $target = max(1, $intention->target_min);

            for ($i = 0; $i < $target; $i++) {
                $slot = array_shift($slots);

                if (! $slot) {
                    $items[] = new PlanItemData($intention->id, $intention->title, $intention->category, null, null, 'unplaceable', 'Geen passend vrij blok gevonden');

                    continue;
                }

                $item = new PlanItemData($intention->id, $intention->title, $intention->category, $slot['start'], $slot['end']);
                $items[] = $item;
                $placed[] = new BusyTime($slot['start'], $slot['end']);
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

        return "Je weekplan staat klaar met {$placed} voorgestelde blokken".($unplaced > 0 ? " en {$unplaced} niet geplaatste intenties." : '.');
    }
}
