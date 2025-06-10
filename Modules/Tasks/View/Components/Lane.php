<?php

namespace Modules\Tasks\View\Components;

use Illuminate\View\Component;
use Modules\Tasks\Models\Lane as LaneModel;

class Lane extends Component
{
    public LaneModel $lane;

    /**
     * Create a new component instance.
     *
     * @param LaneModel $lane
     */
    public function __construct(LaneModel $lane)
    {
        $this->lane = $lane;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('tasks::components.lane');
    }
}
