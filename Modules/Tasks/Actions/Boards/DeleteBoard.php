<?php

namespace Modules\Tasks\Actions\Boards;

use Modules\Tasks\Models\TaskBoard;

class DeleteBoard
{
    public function __construct(
        private readonly EnsureDefaultBoard $ensureDefaultBoard,
    ) {
    }

    public function __invoke(TaskBoard $board): TaskBoard
    {
        $board->delete();

        return ($this->ensureDefaultBoard)();
    }
}
