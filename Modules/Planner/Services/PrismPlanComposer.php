<?php

namespace Modules\Planner\Services;

use Modules\Planner\Contracts\PlanComposer;
use Modules\Planner\Data\ComposedPlan;

class PrismPlanComposer implements PlanComposer
{
    public function compose(array $items, array $busy): ComposedPlan
    {
        $placed = collect($items)->where('status', 'proposed')->count();
        $unplaced = collect($items)->where('status', 'unplaceable')->count();
        $summary = "Deze week zijn {$placed} blokken voorgesteld.";

        if ($unplaced > 0) {
            $summary .= " {$unplaced} intentie(s) pasten niet.";
        }

        // This composer does not yet perform a real Prism/AI arrangement: it keeps the deterministic
        // placement and only writes a summary. `is_fallback` means "AI arrangement was not used", so it is
        // honestly true here. When a real AI arrangement is wired up, set this to false only when the AI
        // actually re-arranged the items.
        return new ComposedPlan($summary, $items, true);
    }
}
