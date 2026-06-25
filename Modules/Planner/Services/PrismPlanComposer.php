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

        return new ComposedPlan($summary, $items, (string) config('ai.anthropic.api_key', '') === '');
    }
}
