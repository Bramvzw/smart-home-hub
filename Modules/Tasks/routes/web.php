<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Http\NotificationController;
use Illuminate\Support\Facades\Route;
use Modules\Tasks\app\Models\Lane;

Route::prefix('tasks')->group(function () {

    Route::get('/', function () {
        $lanes = Lane::with('tasks')->orderBy('position')->get();
        return view('tasks::tasks-board', compact('lanes'));
    });

    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('tasks.notifications');
});
