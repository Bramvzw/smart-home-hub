<?php

use Illuminate\Support\Facades\Route;
use Modules\Tasks\Http\Controllers\TasksController;

Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::get('/', [TasksController::class, 'index'])->name('index');

    // Lane management
    Route::post('/lanes', [TasksController::class, 'createLane'])->name('lanes.create');
    Route::put('/lanes/{id}', [TasksController::class, 'updateLane'])->name('lanes.update');
    Route::delete('/lanes/{id}', [TasksController::class, 'deleteLane'])->name('lanes.delete');

    // Task search and filtering
    Route::get('/search', [TasksController::class, 'searchTasks'])->name('tasks.search');

    // Task management
    Route::post('/tasks', [TasksController::class, 'createTask'])->name('tasks.create');
    Route::put('/tasks/{id}', [TasksController::class, 'updateTask'])->name('tasks.update');
    Route::put('/tasks/{id}/move', [TasksController::class, 'moveTask'])->name('tasks.move');
    Route::delete('/tasks/{id}', [TasksController::class, 'deleteTask'])->name('tasks.delete');

    // Task attachments
    Route::post('/tasks/{id}/attachments', [TasksController::class, 'uploadTaskAttachment'])->name('tasks.attachments.upload');
    Route::delete('/tasks/{taskId}/attachments/{attachmentId}', [TasksController::class, 'deleteTaskAttachment'])->name('tasks.attachments.delete');

    // Task checklists
    Route::post('/tasks/{id}/checklists', [TasksController::class, 'addTaskChecklist'])->name('tasks.checklists.create');
    Route::put('/tasks/{taskId}/checklists/{checklistId}', [TasksController::class, 'updateTaskChecklist'])->name('tasks.checklists.update');
    Route::delete('/tasks/{taskId}/checklists/{checklistId}', [TasksController::class, 'deleteTaskChecklist'])->name('tasks.checklists.delete');

    // Checklist items
    Route::post('/tasks/{taskId}/checklists/{checklistId}/items', [TasksController::class, 'addChecklistItem'])->name('tasks.checklists.items.create');
    Route::put('/tasks/{taskId}/checklists/{checklistId}/items/{itemId}', [TasksController::class, 'updateChecklistItem'])->name('tasks.checklists.items.update');
    Route::put('/tasks/{taskId}/checklists/{checklistId}/items/{itemId}/toggle', [TasksController::class, 'toggleChecklistItemCompletion'])->name('tasks.checklists.items.toggle');
    Route::delete('/tasks/{taskId}/checklists/{checklistId}/items/{itemId}', [TasksController::class, 'deleteChecklistItem'])->name('tasks.checklists.items.delete');

    // Task dependencies
    Route::post('/tasks/{id}/dependencies', [TasksController::class, 'addTaskDependency'])->name('tasks.dependencies.add');
    Route::delete('/tasks/{id}/dependencies/{dependsOnTaskId}', [TasksController::class, 'removeTaskDependency'])->name('tasks.dependencies.remove');
});
