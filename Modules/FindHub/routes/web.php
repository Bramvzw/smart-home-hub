<?php

use Illuminate\Support\Facades\Route;
use Modules\FindHub\Http\Controllers\FindHubController;

Route::prefix('find-hub')->name('findhub.')->group(function (): void {
    Route::get('/', [FindHubController::class, 'index'])->name('index');
});
