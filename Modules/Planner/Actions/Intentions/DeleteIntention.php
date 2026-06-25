<?php

namespace Modules\Planner\Actions\Intentions;

use Modules\Planner\Models\PlannerIntention;

class DeleteIntention
{
    public function __invoke(PlannerIntention $intention): void
    {
        $intention->delete();
    }
}
