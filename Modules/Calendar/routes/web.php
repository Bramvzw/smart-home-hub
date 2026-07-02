<?php

use Illuminate\Support\Facades\Route;
use Modules\Calendar\Http\Controllers\CalendarController;

Route::prefix('calendar')->name('calendar.')->group(function (): void {
    Route::get('/', [CalendarController::class, 'index'])->name('index');

    Route::post('/generate', [CalendarController::class, 'generate'])->name('generate');
    Route::post('/items/{item}/accept', [CalendarController::class, 'acceptItem'])->name('items.accept');
    Route::post('/accept-all', [CalendarController::class, 'acceptAll'])->name('accept-all');
    Route::post('/items/{item}/reject', [CalendarController::class, 'rejectItem'])->name('items.reject');

    Route::get('/goals', [CalendarController::class, 'goals'])->name('goals.index');
    Route::post('/goals', [CalendarController::class, 'storeGoal'])->name('goals.store');
    Route::patch('/goals/{goal}', [CalendarController::class, 'updateGoal'])->name('goals.update');
    Route::delete('/goals/{goal}', [CalendarController::class, 'destroyGoal'])->name('goals.destroy');

    Route::post('/habits/{habit}/complete', [CalendarController::class, 'completeHabit'])->name('habits.complete');
    Route::delete('/habits/{habit}/complete', [CalendarController::class, 'undoHabit'])->name('habits.complete.destroy');

    Route::get('/google/connect', [CalendarController::class, 'connect'])->name('google.connect');
    Route::get('/google/callback', [CalendarController::class, 'callback'])->name('google.callback');
});
