<?php

use Illuminate\Support\Facades\Route;
use Modules\Entertainment\Http\Controllers\EntertainmentController;

Route::prefix('entertainment')->name('entertainment.')->group(function (): void {
    Route::get('/', [EntertainmentController::class, 'index'])->name('index');
    Route::get('/concerts', [EntertainmentController::class, 'concerts'])->name('concerts.index');
    Route::post('/films/{film}/feedback', [EntertainmentController::class, 'feedback'])->name('films.feedback');
    Route::post('/films/{film}/dismiss', [EntertainmentController::class, 'dismiss'])->name('films.dismiss');
    Route::get('/taste', [EntertainmentController::class, 'taste'])->name('taste.show');
    Route::put('/taste', [EntertainmentController::class, 'updateTaste'])->name('taste.update');
    Route::post('/refresh', [EntertainmentController::class, 'refresh'])->middleware('throttle:6,1')->name('refresh');
});
