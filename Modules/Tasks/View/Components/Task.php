<?php

namespace Modules\Tasks\View\Components;

use Illuminate\View\Component;
use Modules\Tasks\Models\Task as TaskModel;

class Task extends Component
{
    public TaskModel $task;

    /**
     * Create a new component instance.
     *
     * @param TaskModel $task
     */
    public function __construct(TaskModel $task)
    {
        $this->task = $task;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('tasks::components.task');
    }
}
