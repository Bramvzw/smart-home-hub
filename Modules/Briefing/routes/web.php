<?php

use Illuminate\Support\Facades\Route;
use Modules\Briefing\Http\Controllers\BriefingController;

Route::prefix('briefing')->name('briefing.')->group(function (): void {
    Route::get('/', [BriefingController::class, 'index'])->name('index');
    Route::post('/regenerate', [BriefingController::class, 'regenerate'])
        ->middleware('throttle:6,1')
        ->name('regenerate');
});
