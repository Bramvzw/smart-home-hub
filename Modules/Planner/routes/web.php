<?php

use Illuminate\Support\Facades\Route;
use Modules\Planner\Http\Controllers\PlannerController;

Route::prefix('planner')->name('planner.')->group(function (): void {
    Route::get('/', [PlannerController::class, 'index'])->name('index');
    Route::post('/generate', [PlannerController::class, 'generate'])->name('generate');
    Route::post('/items/{item}/accept', [PlannerController::class, 'acceptItem'])->name('items.accept');
    Route::post('/accept-all', [PlannerController::class, 'acceptAll'])->name('accept-all');
    Route::post('/items/{item}/reject', [PlannerController::class, 'rejectItem'])->name('items.reject');
    Route::get('/intentions', [PlannerController::class, 'intentions'])->name('intentions.index');
    Route::post('/intentions', [PlannerController::class, 'storeIntention'])->name('intentions.store');
    Route::patch('/intentions/{intention}', [PlannerController::class, 'updateIntention'])->name('intentions.update');
    Route::delete('/intentions/{intention}', [PlannerController::class, 'destroyIntention'])->name('intentions.destroy');
    Route::get('/google/connect', [PlannerController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [PlannerController::class, 'callback'])->name('google.callback');
});
