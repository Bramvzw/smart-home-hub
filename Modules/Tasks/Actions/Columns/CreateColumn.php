<?php

namespace Modules\Tasks\Actions\Columns;

use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskColumn;

class CreateColumn
{
    public function __invoke(TaskBoard $board, string $name): TaskColumn
    {
        return $board->columns()->create([
            'name' => $name,
            'position' => (int) $board->columns()->max('position') + 1,
        ]);
    }
}
