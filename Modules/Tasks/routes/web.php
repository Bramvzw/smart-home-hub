<?php

use Modules\Tasks\Http\Controllers\NotificationController;
use Modules\Tasks\Http\Controllers\TasksController;
use Illuminate\Support\Facades\Route;

Route::prefix('tasks')->group(function () {

    Route::get('/', [TasksController::class, 'index'])->name('tasks.index');

    Route::post('/lanes', [TasksController::class, 'storeLane'])->name('tasks.lanes.store');
    Route::put('/lanes/{lane}', [TasksController::class, 'updateLane'])->name('tasks.lanes.update');
    Route::delete('/lanes/{lane}', [TasksController::class, 'destroyLane'])->name('tasks.lanes.destroy');

    Route::post('/tasks', [TasksController::class, 'storeTask'])->name('tasks.tasks.store');
    Route::put('/tasks/{task}', [TasksController::class, 'updateTask'])->name('tasks.tasks.update');
    Route::put('/tasks/{task}/move', [TasksController::class, 'moveTask'])->name('tasks.tasks.move');
    Route::delete('/tasks/{task}', [TasksController::class, 'destroyTask'])->name('tasks.tasks.destroy');

    Route::get('/tasks/search', [TasksController::class, 'search'])->name('tasks.tasks.search');

    Route::post('/tasks/{task}/attachments', [TasksController::class, 'storeAttachment'])->name('tasks.tasks.attachments.store');
    Route::delete('/tasks/{task}/attachments/{attachment}', [TasksController::class, 'destroyAttachment'])->name('tasks.tasks.attachments.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('tasks.notifications');
});
