<?php

use Illuminate\Support\Facades\Route;
use Modules\Lighting\Http\Controllers\LightingController;

Route::prefix('lighting')->name('lighting.')->group(function (): void {
    Route::get('/', [LightingController::class, 'index'])->name('index');
    Route::get('/state', [LightingController::class, 'state'])->name('state');
    Route::post('/presets/{preset}', [LightingController::class, 'applyPreset'])->name('presets.apply');
    Route::put('/lights', [LightingController::class, 'updateMany'])->name('lights.update-many');
    Route::put('/lights/{provider}/{id}', [LightingController::class, 'update'])->name('lights.update');
});
