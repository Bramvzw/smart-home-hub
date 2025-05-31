<?php

use Illuminate\Support\Facades\Route;
use Modules\Spotify\Http\Controllers\SpotifyController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('spotifies', SpotifyController::class)->names('spotify');
});
