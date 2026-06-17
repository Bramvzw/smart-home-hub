<?php

namespace Modules\FindHub\Http\Controllers;

use Illuminate\Contracts\View\View;

class FindHubController
{
    public function index(): View
    {
        return view('findhub::index', [
            'url' => (string) config('findhub.url'),
        ]);
    }
}
