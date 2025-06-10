<?php

namespace Modules\Tasks\View\Components;

use Illuminate\View\Component;
use Illuminate\Database\Eloquent\Collection;

class TasksBoard extends Component
{
    public Collection $lanes;

    /**
     * Create a new component instance.
     *
     * @param Collection $lanes
     */
    public function __construct(Collection $lanes)
    {
        $this->lanes = $lanes;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('tasks::tasks-board');
    }
}
