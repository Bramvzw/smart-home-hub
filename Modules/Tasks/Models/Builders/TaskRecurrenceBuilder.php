<?php

namespace Modules\Tasks\Models\Builders;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class TaskRecurrenceBuilder extends Builder
{
    public function habits(): static
    {
        return $this->where('type', 'habit');
    }

    public function maintenance(): static
    {
        return $this->where('type', 'maintenance');
    }

    public function active(): static
    {
        return $this->where('active', true);
    }

    public function dueOn(CarbonInterface $date): static
    {
        return $this->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', $date->toDateString());
    }
}
