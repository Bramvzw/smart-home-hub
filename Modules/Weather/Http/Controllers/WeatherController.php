<?php

namespace Modules\Weather\Http\Controllers;

use Illuminate\Contracts\View\View;
use Modules\Weather\View\ViewModels\WeatherViewModel;

class WeatherController
{
    public function index(WeatherViewModel $viewModel): View
    {
        return view('weather::index', $viewModel->page());
    }
}
