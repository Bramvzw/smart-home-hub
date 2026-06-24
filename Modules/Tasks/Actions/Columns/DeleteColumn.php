<?php

namespace Modules\Tasks\Actions\Columns;

use Modules\Tasks\Models\TaskBoard;
use Modules\Tasks\Models\TaskColumn;

class DeleteColumn
{
    public function __construct(
        private readonly ResequenceColumns $resequenceColumns,
    ) {
    }

    public function __invoke(TaskColumn $column): TaskBoard
    {
        abort_if($column->board->columns()->count() <= 1, 422, 'A board needs at least one column.');

        $board = $column->board;
        $column->delete();

        ($this->resequenceColumns)($board, $board->columns()->pluck('id')->all());

        return $board;
    }
}
