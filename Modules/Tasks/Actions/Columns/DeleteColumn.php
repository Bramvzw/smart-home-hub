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
        abort_if($column->board->columns()->count() <= 1, 422, 'Een bord heeft minstens één kolom nodig.');

        $board = $column->board;
        $column->delete();

        ($this->resequenceColumns)($board, $board->columns()->pluck('id')->all());

        return $board;
    }
}
