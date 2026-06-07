<?php

namespace Modules\Tasks\Actions\Boards;

use Modules\Tasks\Models\TaskBoard;

class EnsureDefaultBoard
{
    public function __invoke(): TaskBoard
    {
        $board = TaskBoard::query()->orderBy('position')->first();

        if ($board) {
            return $board;
        }

        $board = TaskBoard::query()->create([
            'name' => 'Tasks',
            'position' => 0,
        ]);

        foreach (['Todo', 'Doing', 'Done'] as $position => $name) {
            $board->columns()->create([
                'name' => $name,
                'position' => $position,
            ]);
        }

        return $board;
    }
}
