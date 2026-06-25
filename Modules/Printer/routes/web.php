<?php

use Illuminate\Support\Facades\Route;
use Modules\Printer\Http\Controllers\FilamentSpoolController;
use Modules\Printer\Http\Controllers\PrinterController;
use Modules\Printer\Http\Controllers\PrinterPartController;

Route::prefix('printer')->name('printer.')->group(function () {
    Route::get('/', [PrinterController::class, 'index'])->name('index');

    Route::post('/filament', [FilamentSpoolController::class, 'store'])->name('filament.store');
    Route::patch('/filament/{spool}', [FilamentSpoolController::class, 'update'])->name('filament.update');
    Route::delete('/filament/{spool}', [FilamentSpoolController::class, 'destroy'])->name('filament.destroy');
    Route::post('/filament/{spool}/adjust', [FilamentSpoolController::class, 'adjust'])->name('filament.adjust');

    Route::post('/parts', [PrinterPartController::class, 'store'])->name('parts.store');
    Route::patch('/parts/{part}', [PrinterPartController::class, 'update'])->name('parts.update');
    Route::delete('/parts/{part}', [PrinterPartController::class, 'destroy'])->name('parts.destroy');
    Route::post('/parts/{part}/adjust', [PrinterPartController::class, 'adjust'])->name('parts.adjust');
});
