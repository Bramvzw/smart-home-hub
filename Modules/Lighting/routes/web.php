<?php

use Illuminate\Support\Facades\Route;
use Modules\Lighting\Http\Controllers\LightingController;
use Modules\Lighting\Http\Controllers\LightingSceneController;

Route::prefix('lighting')->name('lighting.')->group(function (): void {
    Route::get('/', [LightingController::class, 'index'])->name('index');
    Route::get('/state', [LightingController::class, 'state'])->name('state');
    Route::post('/presets/{preset}', [LightingController::class, 'applyPreset'])->name('presets.apply');
    Route::put('/lights', [LightingController::class, 'updateMany'])->name('lights.update-many');
    Route::put('/lights/{provider}/{id}', [LightingController::class, 'update'])->name('lights.update');

    Route::get('/scenes', [LightingSceneController::class, 'index'])->name('scenes.index');
    Route::post('/scenes', [LightingSceneController::class, 'store'])->name('scenes.store');
    Route::put('/scenes/{scene}', [LightingSceneController::class, 'update'])->name('scenes.update');
    Route::delete('/scenes/{scene}', [LightingSceneController::class, 'destroy'])->name('scenes.destroy');
    Route::post('/scenes/{scene}/apply', [LightingSceneController::class, 'apply'])->name('scenes.apply');
});
