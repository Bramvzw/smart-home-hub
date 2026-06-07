<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\TasksController;

Route::prefix('tasks')->name('tasks.')->group(function (): void {
    Route::get('/', [TasksController::class, 'index'])->name('index');

    Route::post('/boards', [TasksController::class, 'storeBoard'])->name('boards.store');
    Route::put('/boards/{board}', [TasksController::class, 'updateBoard'])->name('boards.update');
    Route::delete('/boards/{board}', [TasksController::class, 'destroyBoard'])->name('boards.destroy');
    Route::post('/boards/{board}/columns', [TasksController::class, 'storeColumn'])->name('columns.store');
    Route::put('/columns/{column}', [TasksController::class, 'updateColumn'])->name('columns.update');
    Route::delete('/columns/{column}', [TasksController::class, 'destroyColumn'])->name('columns.destroy');
    Route::post('/boards/{board}/columns/reorder', [TasksController::class, 'reorderColumns'])->name('columns.reorder');

    Route::post('/boards/{board}/tasks', [TasksController::class, 'storeTask'])->name('tasks.store');
    Route::put('/tasks/{task}', [TasksController::class, 'updateTask'])->name('tasks.update');
    Route::put('/tasks/{task}/move', [TasksController::class, 'moveTask'])->name('tasks.move');
    Route::post('/tasks/{task}/archive', [TasksController::class, 'archiveTask'])->name('tasks.archive');
    Route::delete('/tasks/{task}', [TasksController::class, 'destroyTask'])->name('tasks.destroy');
});
