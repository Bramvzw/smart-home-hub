<?php

use Illuminate\Support\Facades\Route;
use Modules\Lighting\Http\Controllers\LightingController;

Route::prefix('lighting')->name('lighting.')->group(function (): void {
    Route::get('/', [LightingController::class, 'index'])->name('index');
    Route::post('/presets/{preset}', [LightingController::class, 'applyPreset'])->name('presets.apply');
    Route::put('/lights/{provider}/{id}', [LightingController::class, 'update'])->name('lights.update');
});
