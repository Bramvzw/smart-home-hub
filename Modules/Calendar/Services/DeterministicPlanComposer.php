<?php

namespace Modules\Calendar\Services;

use Modules\Calendar\Contracts\PlanComposer;
use Modules\Calendar\Data\ComposedPlan;

class DeterministicPlanComposer implements PlanComposer
{
    public function compose(array $items, array $busy): ComposedPlan
    {
        $placed = collect($items)->where('status', 'proposed')->count();
        $unplaced = collect($items)->where('status', 'unplaceable')->count();
        $summary = "{$placed} blocks proposed this week.";

        if ($unplaced > 0) {
            $summary .= " {$unplaced} habit(s) didn't fit.";
        }

        // No AI arrangement yet, so is_fallback stays true until this composer re-arranges items itself.
        return new ComposedPlan($summary, $items, true);
    }
}
