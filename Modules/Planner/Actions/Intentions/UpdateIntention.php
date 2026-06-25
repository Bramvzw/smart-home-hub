<?php

namespace Modules\Planner\Actions\Intentions;

use Modules\Planner\Models\PlannerIntention;

class UpdateIntention
{
    public function __invoke(PlannerIntention $intention, array $data): PlannerIntention
    {
        $intention->update($data);

        return $intention->fresh();
    }
}
