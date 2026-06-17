<?php

use Illuminate\Support\Facades\Route;
use Modules\PhonePing\Http\Controllers\PhonePingController;

Route::prefix('phone-ping')->name('phoneping.')->middleware('throttle:10,1')->group(function (): void {
    Route::get('/', [PhonePingController::class, 'index'])->name('index');
    Route::post('/', [PhonePingController::class, 'ping'])->name('ping');
});
