<?php

namespace Modules\Tasks\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Modules\Tasks\Models\Task;

class NotificationController
{
    public function index(): View
    {
        $tasksAboutToExpire = Task::whereNotNull('due_date')
            ->where('due_date', '>', now())
            ->where('due_date', '<=', now()->addDays(2))
            ->get();

        $overdueTasks = Task::whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->get();

        return view('tasks::notifications', compact('tasksAboutToExpire', 'overdueTasks'));
    }
}
