<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings/modules', [SettingsController::class, 'updateModules'])->name('settings.modules.update');
Route::put('/settings/{module}', [SettingsController::class, 'update'])->name('settings.update');
