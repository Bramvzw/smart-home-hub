<?php

namespace Modules\Tasks\Actions\Boards;

use Modules\Tasks\Models\TaskBoard;

class CreateBoard
{
    public function __invoke(string $name): TaskBoard
    {
        $board = TaskBoard::query()->create([
            'name' => $name,
            'position' => (int) TaskBoard::query()->max('position') + 1,
        ]);

        foreach (['Todo', 'Doing', 'Done'] as $position => $columnName) {
            $board->columns()->create([
                'name' => $columnName,
                'position' => $position,
            ]);
        }

        return $board;
    }
}
