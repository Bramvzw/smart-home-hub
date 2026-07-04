<?php

use Illuminate\Support\Facades\Route;
use Modules\Weather\Http\Controllers\WeatherController;

Route::prefix('weather')->name('weather.')->group(function (): void {
    Route::get('/', [WeatherController::class, 'index'])->name('index');
});
