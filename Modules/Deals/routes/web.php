<?php

use Illuminate\Support\Facades\Route;
use Modules\Deals\Http\Controllers\DealsController;

Route::prefix('deals')->name('deals.')->group(function (): void {
    Route::get('/', [DealsController::class, 'index'])->name('index');
    Route::post('/products', [DealsController::class, 'storeProduct'])->name('products.store');
    Route::get('/products/{product}/history', [DealsController::class, 'history'])->name('products.history');
    Route::post('/listings/{listing}/confirm', [DealsController::class, 'confirmListing'])->name('listings.confirm');
    Route::delete('/listings/{listing}', [DealsController::class, 'destroyListing'])->name('listings.destroy');
    Route::post('/check', [DealsController::class, 'check'])->name('check');
});
