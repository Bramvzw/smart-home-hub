<?php

use Illuminate\Support\Facades\Route;
use Modules\Spotify\Http\Controllers\SpotifyController;

Route::prefix('spotify')->name('spotify.')->group(function () {
    Route::get('/', [SpotifyController::class, 'index'])->name('index');
    Route::get('/callback', [SpotifyController::class, 'callback'])->name('callback');

    // Playback control
    Route::post('/play', [SpotifyController::class, 'play'])->name('play');
    Route::post('/pause', [SpotifyController::class, 'pause'])->name('pause');
    Route::post('/next', [SpotifyController::class, 'next'])->name('next');
    Route::post('/previous', [SpotifyController::class, 'previous'])->name('previous');
    Route::post('/volume', [SpotifyController::class, 'setVolume'])->name('volume');
    Route::post('/seek', [SpotifyController::class, 'seekToPosition'])->name('seek');
    Route::get('/playback-state', [SpotifyController::class, 'getPlaybackState'])->name('playback-state');

    // User playlists and next track
    Route::get('/user-playlists', [SpotifyController::class, 'getUserPlaylists'])->name('user-playlists');
    Route::get('/next-track', [SpotifyController::class, 'getNextTrack'])->name('next-track');
    Route::post('/shuffle-play-playlist', [SpotifyController::class, 'shufflePlayPlaylist'])->name('shuffle-play-playlist');

    // Library management
    Route::get('/tracks/check', [SpotifyController::class, 'checkSavedTracks'])->name('check-saved-tracks');
    Route::post('/tracks/toggle', [SpotifyController::class, 'toggleSaveTrack'])->name('toggle-save-track');
});
