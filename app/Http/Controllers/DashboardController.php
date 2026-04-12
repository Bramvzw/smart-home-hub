<?php

namespace App\Http\Controllers;

use App\Services\ModuleRegistry;

class DashboardController
{
    public function index(ModuleRegistry $registry)
    {
        return view('dashboard', [
            'modules' => $registry->getModules(),
        ]);
    }
}
