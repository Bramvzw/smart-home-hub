<?php

namespace Modules\Tasks\Actions\Recurrences;

use Modules\Tasks\Models\TaskRecurrence;

class DeleteRecurrence
{
    public function __invoke(TaskRecurrence $recurrence): void
    {
        $recurrence->delete();
    }
}
