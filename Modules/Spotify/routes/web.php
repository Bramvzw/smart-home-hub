<?php

use Illuminate\Support\Facades\Route;
use Modules\Spotify\Http\Controllers\SpotifyController;

Route::prefix('spotify')->name('spotify.')->group(function () {
    Route::get('/', [SpotifyController::class, 'index'])->name('index');
    Route::get('/callback', [SpotifyController::class, 'callback'])->name('callback');

    // Playback control
    Route::post('/play', [SpotifyController::class, 'play'])->name('play')->middleware('throttle:spotify-write');
    Route::post('/pause', [SpotifyController::class, 'pause'])->name('pause')->middleware('throttle:spotify-write');
    Route::post('/next', [SpotifyController::class, 'next'])->name('next')->middleware('throttle:spotify-write');
    Route::post('/previous', [SpotifyController::class, 'previous'])->name('previous')->middleware('throttle:spotify-write');
    Route::post('/volume', [SpotifyController::class, 'setVolume'])->name('volume')->middleware('throttle:spotify-write');
    Route::post('/seek', [SpotifyController::class, 'seekToPosition'])->name('seek')->middleware('throttle:spotify-write');
    Route::get('/playback-state', [SpotifyController::class, 'getPlaybackState'])->name('playback-state')->middleware('throttle:spotify-read');

    // User playlists and next track
    Route::get('/user-playlists', [SpotifyController::class, 'getUserPlaylists'])->name('user-playlists')->middleware('throttle:spotify-read');
    Route::get('/next-track', [SpotifyController::class, 'getNextTrack'])->name('next-track')->middleware('throttle:spotify-read');
    Route::post('/shuffle-play-playlist', [SpotifyController::class, 'shufflePlayPlaylist'])->name('shuffle-play-playlist')->middleware('throttle:spotify-write');

    // Shuffle & repeat
    Route::post('/shuffle', [SpotifyController::class, 'setShuffle'])->name('shuffle')->middleware('throttle:spotify-write');
    Route::post('/repeat', [SpotifyController::class, 'setRepeatMode'])->name('repeat')->middleware('throttle:spotify-write');

    // Devices
    Route::get('/devices', [SpotifyController::class, 'getDevices'])->name('devices')->middleware('throttle:spotify-read');
    Route::post('/transfer-playback', [SpotifyController::class, 'transferPlayback'])->name('transfer-playback')->middleware('throttle:spotify-write');

    // Browse
    Route::get('/recently-played', [SpotifyController::class, 'getRecentlyPlayed'])->name('recently-played')->middleware('throttle:spotify-read');
    Route::get('/search', [SpotifyController::class, 'search'])->name('search')->middleware('throttle:spotify-search');
    Route::get('/queue', [SpotifyController::class, 'getQueue'])->name('queue')->middleware('throttle:spotify-read');
    Route::post('/add-to-queue', [SpotifyController::class, 'addToQueue'])->name('add-to-queue')->middleware('throttle:spotify-write');

    // Library management
    Route::get('/tracks/check', [SpotifyController::class, 'checkSavedTracks'])->name('check-saved-tracks')->middleware('throttle:spotify-read');
    Route::post('/tracks/toggle', [SpotifyController::class, 'toggleSaveTrack'])->name('toggle-save-track')->middleware('throttle:spotify-write');
});
