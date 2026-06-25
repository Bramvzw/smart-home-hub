<?php

use Illuminate\Support\Facades\Route;
use Modules\Recipes\Http\Controllers\RecipesController;

Route::prefix('recipes')->name('recipes.')->group(function (): void {
    Route::get('/', [RecipesController::class, 'index'])->name('index');
    Route::get('/offers', [RecipesController::class, 'offers'])->name('offers.index');
    Route::get('/{recipe}', [RecipesController::class, 'show'])->name('show');
    Route::post('/generate', [RecipesController::class, 'generate'])->name('generate');
});
