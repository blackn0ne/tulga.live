<?php

use App\Http\Controllers\ClassController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('classes', ClassController::class)
        ->except('show')
        ->parameters(['classes' => 'schoolClass']);

    Route::post('lessons/{lesson}/start', [LessonController::class, 'start'])
        ->name('lessons.start');
    Route::post('lessons/{lesson}/finish', [LessonController::class, 'finish'])
        ->name('lessons.finish');

    Route::resource('lessons', LessonController::class);

    Route::resource('subjects', SubjectController::class)
        ->except('show');

    Route::resource('users', UserController::class)
        ->except('show');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
