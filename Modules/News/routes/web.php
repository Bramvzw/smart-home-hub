<?php

use Illuminate\Support\Facades\Route;
use Modules\News\Http\Controllers\NewsController;

Route::prefix('news')->name('news.')->group(function (): void {
    Route::get('/', [NewsController::class, 'index'])->name('index');
    Route::get('/items', [NewsController::class, 'items'])->name('items.index');
    Route::post('/items/{item}/read', [NewsController::class, 'markRead'])->name('items.read');
    Route::post('/read-all', [NewsController::class, 'readAll'])->name('read-all');
    Route::post('/refresh', [NewsController::class, 'refresh'])->name('refresh');
});
