<?php

namespace Modules\Tasks\Actions\Recurrences;

use Modules\Tasks\Models\TaskRecurrence;

class UpdateRecurrence
{
    public function __invoke(TaskRecurrence $recurrence, array $data): TaskRecurrence
    {
        $recurrence->update(collect($data)->only([
            'board_id',
            'type',
            'title',
            'description',
            'cadence_type',
            'cadence_config',
            'notify',
            'active',
            'next_due_on',
            'last_materialized_on',
        ])->all());

        return $recurrence->fresh();
    }
}
