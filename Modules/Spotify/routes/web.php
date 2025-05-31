<?php

use Illuminate\Support\Facades\Route;
use Modules\Spotify\Http\Controllers\SpotifyController;

Route::prefix('spotify')->name('spotify.')->group(function () {
    Route::get('/', [SpotifyController::class, 'index'])->name('index');
    Route::get('/callback', [SpotifyController::class, 'callback'])->name('callback');

    Route::post('/play', [SpotifyController::class, 'play'])->name('play');
    Route::post('/pause', [SpotifyController::class, 'pause'])->name('pause');
    Route::post('/next', [SpotifyController::class, 'next'])->name('next');
    Route::post('/previous', [SpotifyController::class, 'previous'])->name('previous');
    Route::post('/volume', [SpotifyController::class, 'setVolume'])->name('volume');
    Route::get('/playback-state', [SpotifyController::class, 'getPlaybackState'])->name('playback-state');
});

