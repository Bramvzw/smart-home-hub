<?php

return [
    'name' => 'Tasks',
    'recurrence' => [
        'maintenance_board' => env('TASKS_MAINTENANCE_BOARD', 'Tasks'),
        'maintenance_column' => env('TASKS_MAINTENANCE_COLUMN', 'Todo'),
        'notify' => true,
    ],
];
