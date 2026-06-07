<?php

namespace Modules\Tasks\Actions\Columns;

use Modules\Tasks\Models\TaskBoard;

class ResequenceColumns
{
    public function __invoke(TaskBoard $board, array $orderedIds): void
    {
        collect($orderedIds)->values()->each(function (int $columnId, int $position) use ($board): void {
            $board->columns()->whereKey($columnId)->update(['position' => $position]);
        });
    }
}
