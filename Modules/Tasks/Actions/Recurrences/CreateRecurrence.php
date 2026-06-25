<?php

namespace Modules\Tasks\Actions\Recurrences;

use Modules\Tasks\Models\TaskRecurrence;

class CreateRecurrence
{
    public function __invoke(array $data): TaskRecurrence
    {
        return TaskRecurrence::query()->create($this->normalize($data));
    }

    private function normalize(array $data): array
    {
        return [
            'board_id' => $data['board_id'] ?? null,
            'type' => $data['type'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'cadence_type' => $data['cadence_type'],
            'cadence_config' => $data['cadence_config'] ?? [],
            'notify' => $data['notify'] ?? (bool) config('tasks.recurrence.notify', true),
            'active' => $data['active'] ?? true,
            'next_due_on' => $data['next_due_on'] ?? null,
            'last_materialized_on' => null,
        ];
    }
}
