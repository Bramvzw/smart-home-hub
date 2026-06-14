<?php

namespace Modules\Calendar\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Calendar\View\ViewModels\CalendarViewModel;

class CalendarController
{
    public function index(CalendarViewModel $viewModel): View
    {
        return view('calendar::index', $viewModel->page());
    }
}
