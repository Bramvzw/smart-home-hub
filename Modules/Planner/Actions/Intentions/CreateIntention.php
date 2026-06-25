<?php

namespace Modules\Planner\Actions\Intentions;

use Modules\Planner\Models\PlannerIntention;

class CreateIntention
{
    public function __invoke(array $data): PlannerIntention
    {
        return PlannerIntention::query()->create($data);
    }
}
