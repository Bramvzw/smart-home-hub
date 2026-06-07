<?php

namespace Modules\Tasks\Actions\Columns;

use Modules\Tasks\Models\TaskColumn;

class UpdateColumn
{
    public function __invoke(TaskColumn $column, string $name): TaskColumn
    {
        $column->update(['name' => $name]);
        $column->tasks()->update(['completed' => $column->isDoneColumn()]);

        return $column;
    }
}
