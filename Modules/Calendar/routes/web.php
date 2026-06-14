<?php

use Illuminate\Support\Facades\Route;
use Modules\Calendar\Http\Controllers\CalendarController;

Route::prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
});
