<?php

use App\Http\Controllers\SpotifyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Spotify routes
Route::prefix('spotify')->name('spotify.')->group(function () {
    Route::get('/', [SpotifyController::class, 'index'])->name('index');
    Route::get('/callback', [SpotifyController::class, 'callback'])->name('callback');

    // API routes for controlling playback
    Route::post('/play', [SpotifyController::class, 'play'])->name('play');
    Route::post('/pause', [SpotifyController::class, 'pause'])->name('pause');
    Route::post('/next', [SpotifyController::class, 'next'])->name('next');
    Route::post('/previous', [SpotifyController::class, 'previous'])->name('previous');
    Route::post('/volume', [SpotifyController::class, 'setVolume'])->name('volume');
    Route::get('/playback-state', [SpotifyController::class, 'getPlaybackState'])->name('playback-state');
});
